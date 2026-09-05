# DecorArte · Fiestas Mágicas

Site e backoffice para a **DecorArte — Fiestas Mágicas**, decoração de festas
e cerimónias em Sabarís (Baiona, Pontevedra).

Laravel 13 · Livewire 4 · Filament 5 · Tailwind 4 · PostgreSQL 16 · Debian

---

## Arrancar

Uma vez só, no WSL:

```bash
bash bootstrap.sh
```

O script confirma as ferramentas, traz o esqueleto do Laravel sem tocar em
nada do que já está aqui, instala os pacotes, prepara o `.env` e corre as
migrations. Podes voltar a correr sempre que quiseres.

Se faltar o PHP 8.4 no Debian 12 (só traz o 8.2):

```bash
sudo apt install -y ca-certificates apt-transport-https lsb-release curl gnupg
sudo curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
sudo apt install -y php8.4-cli php8.4-{pgsql,mbstring,xml,curl,zip,gd,intl,bcmath}
```

> O PHP do Windows não serve: vem sem `openssl` e o Composer não consegue
> fazer TLS. Trabalha sempre do lado do WSL.

Depois:

```bash
npm run dev          # num terminal
php artisan serve    # noutro
```

---

## Como está organizado

```
database/schema/      A FONTE DE VERDADE do modelo de dados (SQL + testes)
database/migrations/  geradas a partir do schema — não editar à mão
app/Enums/            gerados a partir dos CHECK do schema
app/Models/           Eloquent
app/Support/          Period, AvailabilityService — a lógica de domínio
lang/{es,gl,pt}/      traduções, verificadas contra os enums
tests/                Pest
```

### O esquema não nasce das migrations

Ao contrário do habitual em Laravel, o modelo de dados vive em
`database/schema/schema.sql` e as migrations são **geradas** a partir dele.

A razão é simples: o esquema usa `tstzrange`, índices `gist`, índices únicos
sobre expressões `jsonb` e triggers em plpgsql. Nada disso se exprime no
Schema builder do Laravel, e escrever tudo em `DB::statement()` disperso por
vinte migrations garante que mais cedo ou mais tarde alguém as põe a divergir
do que foi testado.

Assim há um único ficheiro, com testes próprios, e as migrations são um
produto dele. Para mexer no modelo:

1. editar `database/schema/schema.sql`
2. correr `database/schema/tests.sql` — tem de dar 29/29
3. regerar as migrations

Ver `database/schema/README.md` para o detalhe.

### O que a base de dados garante sozinha

Três coisas estão em triggers, não no PHP. Um bug no código não as contorna:

- **Não há overbooking de peças**, incluindo stock agregado (40 cadeiras em
  reservas de 10 + 15 + 20). Testado também em concorrência: sem o advisory
  lock, 3 em 3 tentativas simultâneas davam overbooking.
- **Baixar o stock não cria overbooking retroativo.**
- **Documentos selados são imutáveis** — `UPDATE` e `DELETE` recusados. É a
  ponta instalada para o Veri*factu (obrigatório para autónomos a partir de
  1 de julho de 2027).

O `AvailabilityService` repete a lógica de disponibilidade em SQL, mas com
outro papel: serve para o calendário dizer "restam 12 cadeiras" *antes* de a
pessoa carregar em reservar, e para dar um erro decente em vez de uma exceção
de SQL. Quem garante continua a ser o trigger.

---

## Testes

```bash
# esquema — SQL puro, sem Laravel pelo meio
psql -h 127.0.0.1 -U fiestas -d fiestas -f database/schema/tests.sql
python3 database/schema/test_concurrency.py

# aplicação
./vendor/bin/pest
./vendor/bin/pint --test        # estilo
./vendor/bin/phpstan analyse    # análise estática
```

Os testes de aplicação correm contra **Postgres, nunca SQLite**: o que está a
ser testado são triggers plpgsql e tipos de intervalo que o SQLite não tem.
Um teste que passe em SQLite aqui não prova nada.

---

## Decisões que vale a pena conhecer

**Postgres e não MariaDB.** `tstzrange` + `gist` é o que torna o controlo de
stock verificável pela base de dados. Em MariaDB isso resolve-se à mão com
locks no código da aplicação, e passa a depender de ninguém se esquecer.

**Estados em `varchar` + `CHECK`, não enums do Postgres.** Acrescentar um
valor a um enum PG é uma migration que bloqueia a tabela.

**Os enums PHP são gerados a partir dos `CHECK`.** Os valores que o PHP
aceita e os que a base de dados aceita são literalmente a mesma lista.

**As traduções são verificadas.** `gen_lang.py` falha se algum valor de enum
ficar sem tradução em `es`, `gl` ou `pt`. São 58 valores × 3 idiomas.

**Dinheiro em `numeric(10,2)` e somado com `bcmath`.** Nunca em vírgula
flutuante.

**IPs guardados só em hash.** Chega para detetar spam da mesma origem sem
armazenar um dado pessoal.

**Publicar fotos exige consentimento.** `projects` tem um `CHECK` que impede
publicar sem `consent_at`: são fotos da festa de outra pessoa.

**Testemunhos exigem `consent_at`, que é `NOT NULL`.** A tabela só aceita
testemunhos reais e autorizados. Nenhum dos sites do setor que serviram de
referência (Decoraciones Kat, Globossol, Globodream) tem testemunhos.

**Mobile-first, e PWA em vez de app nativa.** Uma pessoa organiza um batizado
uma vez na vida; ninguém instala uma app para isso. O tráfego vem do
Instagram, num telemóvel. A PWA serve o lado interno da empresa — checklists
de montagem no local, stock — com o mesmo código e sem App Store.

---

## Estado

| Fase | | |
|---|---|---|
| 0 | Esquema, migrations, models, enums, i18n, CI | feito |
| 1 | Site público: home, portefólio, formulário, SEO | a seguir |
| 2 | Comercial: orçamentos, aprovação online, Stripe, área de cliente | |
| 3 | Operações: catálogo de aluguer, calendário, logística | |
| 4 | Fiscal: séries legais, cadeia de hash, Veri*factu | quando a empresa se formalizar |
