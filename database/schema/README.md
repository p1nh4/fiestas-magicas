# Esquema da base de dados — DecorArte / Fiestas Mágicas

Estes ficheiros são a **fonte de verdade do modelo de dados**. As migrations do
Laravel vão espelhá-los. Estão separados do framework de propósito: assim podem
ser corridos e testados contra um Postgres real sem depender do Laravel.

```
database/schema/
├── schema.sql            # todas as tabelas, índices, CHECKs e triggers
├── tests.sql             # 29 testes das regras de negócio
├── test_concurrency.py   # prova que duas reservas simultâneas não fazem overbooking
└── README.md             # este ficheiro
```

## Correr

No WSL, com o Postgres a andar:

```bash
sudo service postgresql start

# base de dados limpa
sudo -u postgres dropdb --if-exists fiestas
sudo -u postgres createdb -O fiestas fiestas

cd database/schema
PGPASSWORD=fiestas psql -v ON_ERROR_STOP=1 -h 127.0.0.1 -U fiestas -d fiestas -f schema.sql
PGPASSWORD=fiestas psql -h 127.0.0.1 -U fiestas -d fiestas -f tests.sql
```

Esperado: **29 passaram, 0 falharam**.

Teste de concorrência (precisa de `pip install psycopg2-binary`):

```bash
python3 test_concurrency.py    # sai com 0 se passar
```

## As três coisas que a base de dados garante sozinha

Estão em triggers, não na aplicação. Um bug no código PHP não as consegue
contornar — e é esse o objetivo.

### 1. Não há overbooking de peças

O caso difícil não é a peça única, é o **stock agregado**: 40 cadeiras podem
sair em reservas de 10 + 15 + 20 e só a soma é que estoura. Um `EXCLUDE`
constraint do Postgres resolve o primeiro caso mas não sabe somar, por isso
aqui é um trigger.

O algoritmo é um varrimento de linha: o pico de procura de um intervalo só
pode acontecer **no início de alguma reserva**. Basta então testar os inícios
das reservas que se sobrepõem à nova e ver, em cada um, quanto está
simultaneamente reservado.

O `pg_advisory_xact_lock` por artigo é o que impede o *write skew*. Sem ele,
duas transações concorrentes leem ambas "há stock" antes de qualquer uma
escrever, e ambas gravam. Isto foi medido:

| | resultado em 3 tentativas |
|---|---|
| sem advisory lock | 3 × overbooking (2 reservas para 1 peça) |
| com advisory lock | 3 × correto (1 commit, 1 rollback) |

O `period` de uma reserva já inclui as folgas de montagem e limpeza
(`items.buffer_before` / `buffer_after`). Quem escreve calcula a janela
bloqueada; a base de dados garante que ela cabe.

### 2. Baixar o stock não cria overbooking retroativo

Se houver 35 cadeiras reservadas para dali a dois meses, o backoffice não
consegue mudar o stock de 40 para 20. Reservas já passadas são ignoradas.

### 3. Documentos selados são imutáveis

Depois de `locked_at` ficar preenchido, o `UPDATE` e o `DELETE` são recusados
pela base de dados. Com a numeração sequencial por série (`UNIQUE (series_id,
sequence)`) e os campos `content_hash` / `previous_hash`, isto é a
**ponta instalada para o Veri*factu** — obrigatório para autónomos a partir de
1 de julho de 2027. Hoje serve para orçamentos e recibos e não custa nada.

## Decisões que vale a pena conhecer

**Estados em `varchar` + `CHECK`, não em enums do Postgres.** Acrescentar um
valor a um enum PG é uma migration que bloqueia a tabela; um `CHECK` altera-se
sem dor. Os valores são espelhados por enums PHP.

**`timestamptz` em tudo.** A empresa é espanhola mas há clientes no norte de
Portugal, e Espanha muda a hora duas vezes por ano. `timestamp` sem zona
seria uma armadilha.

**Conteúdo traduzível em `jsonb`** (`{"es":…, "gl":…, "pt":…}`), com índices
únicos por locale sobre `slug->>'es'`, `slug->>'gl'` e `slug->>'pt'`. Evita
uma tabela de traduções por cada entidade.

**IPs guardados só como hash** (`ip_hash`), nunca em claro. RGPD.

**Publicar fotos exige consentimento.** `projects` tem
`CHECK (NOT is_published OR consent_at IS NOT NULL)`: sem autorização do
cliente, a base de dados não deixa publicar as fotos da festa dele.

**Testemunhos exigem `consent_at`, que é `NOT NULL`.** A tabela existe, mas
só aceita testemunhos reais e autorizados. Nada de texto inventado — os três
sites do setor que servem de referência (Decoraciones Kat, Globossol,
Globodream) não têm testemunhos nenhuns.

**Datas dos testes relativas a `now()`.** Um teste com datas fixas passa a
mentir assim que essas datas ficam no passado — foi exatamente o que aconteceu
na primeira execução, em que o teste do stock passou por engano.
