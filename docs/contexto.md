# Contexto do projeto — lê isto primeiro

**Este ficheiro é o ponto de entrada.** Quem pegar no projeto — pessoa ou
agente — lê-o inteiro antes de mexer em código ou sugerir comandos.
Substitui o antigo `docs/estado-do-projeto.md`, que tinha factos errados.

Última verificação contra a máquina e o repositório: **2026-09-08**.

> **Regra para agentes:** não assumas nada sobre a máquina, o stack ou o
> estado do deploy. O que está aqui foi verificado; o resto pergunta-se ou
> mede-se. Se descobrires que uma linha deste ficheiro está errada, corrige-a
> na mesma sessão — um doc desatualizado custou horas neste projeto.

---

## 1. O que isto é

Site público + backoffice para a **DecorArte — Fiestas Mágicas**, decoração
de festas e cerimónias em Sabarís, Baiona (Pontevedra, Galiza).

- **Dona do negócio:** Sol Fernández. Decide preços, textos, fotos, horários.
- **Quem programa:** David. Sozinho.
- **Domínio:** `fiestasmagicasengalicia.com`
- **Repositório:** `git@github.com:p1nh4/fiestas-magicas.git`
- **Três mercados:** espanhol (`es`), galego (`gl`), português (`pt`). Não
  são traduções de cortesia — o negócio atravessa a fronteira.

O percurso completo faz-se sem tocar em código: pedido do site → cliente e
evento → projeto (mood board) → orçamento → enviar → cliente aceita
(material fica reservado) → sinal pago.

## 2. Stack

Laravel 13 · PHP **8.4** · **PostgreSQL 16** · Livewire 4 · Filament 5 ·
Tailwind 4 · Vite 8 · Pest 4.7 · spatie/laravel-translatable ·
spatie/laravel-permission · Debian 12

> **Não é MariaDB, não é MySQL, não é PHP 8.3.** O Postgres não é
> preferência: `tstzrange` + índices `gist` são o que torna o controlo de
> stock verificável pela própria base de dados.

## 3. Arquitetura — as três ideias que explicam o resto

### 3.1 O esquema não nasce das migrations

`database/schema/schema.sql` é a **fonte de verdade**. As migrations, os
enums PHP e os ficheiros de idioma são **gerados** a partir dele pelos
scripts em `tools/`.

Porquê: o esquema usa `tstzrange`, índices `gist`, índices únicos sobre
expressões `jsonb` e triggers em plpgsql. Nada disso se exprime no Schema
builder do Laravel.

Ordem ao alterar o modelo:

1. editar `database/schema/schema.sql`
2. `psql ... -f database/schema/tests.sql` → tem de dar 29/29
3. correr os geradores
4. rever o diff e copiar

**Nunca editar à mão** `database/migrations/`, `app/Enums/` ou
`lang/*/*.php`. Edita-se o gerador e corre-se outra vez. Quem editar só o
ficheiro final perde o trabalho na próxima geração.

### 3.2 Os geradores, e onde cada um escreve

| script | lê | escreve |
|---|---|---|
| `tools/gen_migrations.py` | `database/schema/schema.sql` | `build/` |
| `tools/gen_enums.py` | `database/schema/schema.sql` | `build/app/Enums/` + `build/_enum_values.json` |
| `tools/gen_lang.py` | `build/_enum_values.json` | `build/lang/` |
| `tools/gen_site_lang.py` | (tabelas dentro do próprio script) | **`lang/` diretamente** |
| `gen_areas_lang.py`, `gen_mails_lang.py`, `gen_quote_lang.py`, `gen_rentals_lang.py`, `gen_works_lang.py` | idem | `lang/` |

Duas armadilhas confirmadas:

- **`gen_lang.py` depende do `gen_enums.py`.** Corre-o primeiro, senão
  rebenta com `FileNotFoundError: build/_enum_values.json`.
- **O `tools/README.md` diz que todos escrevem para `build/`. Não é
  verdade** — o `gen_site_lang.py` e os outros `gen_*_lang.py` escrevem
  direto em `lang/`.

### 3.3 O que a base de dados garante sozinha

Três coisas vivem em triggers, não em PHP. Um bug no código não as contorna:

- **Não há overbooking de peças**, incluindo stock agregado (40 cadeiras em
  reservas de 10 + 15 + 20). Provado com um teste de concorrência de duas
  threads: sem o advisory lock, 3 em 3 tentativas simultâneas davam
  overbooking.
- **Baixar o stock não cria overbooking retroativo.**
- **Documentos selados são imutáveis** — `UPDATE` e `DELETE` recusados.
  Ponta instalada para o Veri\*factu (obrigatório para autónomos a partir de
  1 de julho de 2027).

O `AvailabilityService` repete a lógica em SQL com outro papel: dizer
"restam 12 cadeiras" *antes* de a pessoa carregar em reservar, e dar um erro
decente em vez de uma exceção de SQL. Quem garante continua a ser o trigger.

## 4. Regras que não se mexem

1. **`schema.sql` é a fonte de verdade.** Ver 3.1.
2. **Nenhuma frase visível dentro de uma view.** Tudo em `lang/{es,gl,pt}/`.
   Os geradores falham, e não escrevem nada, se faltar uma chave num idioma.
3. **O stock é protegido por trigger com advisory lock**, não por
   verificação em PHP. Duas pessoas ao mesmo tempo contornam o PHP.
4. **Um orçamento enviado não se edita** — cria-se a versão seguinte
   (`QuoteBuilder::reviseFrom`). É a única prova do que a cliente viu.
5. **Nunca se dá um pagamento por pago porque alguém abriu uma URL.** Quem
   confirma é o fornecedor, perguntando-lhe. Vale para a volta do browser e
   para o `payments:reconcile`; ambos passam pelo `SettlePayment`.
6. **Nada se publica sem consentimento**: trabalho sem `consent_at`, opinião
   sem consentimento datado, zona sem 200 caracteres de texto próprio. Os
   três são `CHECK` na base de dados.
7. **Não se inventam dados.** Preço a zero mostra "a consultar". Há testes
   que falham se alguém inventar testemunhos ou preços.
8. **Um link que não vai a lado nenhum não se escreve** — fica texto
   simples, nunca `href="#"`.
9. **`trustProxies` é `['127.0.0.1','::1']`, não `'*'`.** Atrás da
   Cloudflare o `X-Forwarded-For` é forjável; quem decide o IP é o nginx a
   partir do `CF-Connecting-IP`. Mexer nisto parte o rate-limit do
   formulário e o `hashIp()` dos leads.
10. **Um projeto (`event_designs`) não reserva material.** Reservar é do
    orçamento aceite.
11. **Dinheiro em `numeric(10,2)`, somado com `bcmath`.** Nunca em vírgula
    flutuante. Sem a extensão `bcmath` os testes de dinheiro falham — e é
    bom que falhem.
12. **IPs guardados só em hash.** Chega para detetar spam da mesma origem
    sem armazenar um dado pessoal.

## 5. Como se trabalha nesta máquina

O David trabalha em **Windows + WSL2 (Debian 12)**. Isto condiciona tudo.

### 5.1 Há duas cópias do repositório

| | onde | papel |
|---|---|---|
| **WSL** | `/home/p1nh4/fiestasmagicasgalicia` | **a cópia real.** É onde se corre, testa e faz commit |
| **Windows** | `C:\Users\Los Pollos\Documents\Projetos\fiestasmagicasgalicia` | ponto de entrega para o agente ler e escrever ficheiros |

Ambas são clones do mesmo remoto. **Já ficaram dessincronizadas uma vez**, e
custou uma sessão a perceber.

### 5.2 O agente não tem shell nesta máquina

O ambiente Linux do Cowork não arranca aqui. O agente **só lê e escreve
ficheiros** dentro da pasta do Windows. Todos os comandos são corridos pelo
David, à mão, no WSL.

**Circuito de leitura.** O agente escreve `_diag.sh` na pasta do Windows; o
David corre-o do WSL e ele grava `_estado.md` na mesma pasta, que o agente
lê. Não imprime segredos — de chaves, passwords e tokens mostra só se estão
preenchidos.

```bash
bash "/mnt/c/Users/Los Pollos/Documents/Projetos/fiestasmagicasgalicia/_diag.sh"
```

**Circuito de escrita.** O agente edita na pasta do Windows; o David revê e
aplica no WSL:

```bash
WIN="/mnt/c/Users/Los Pollos/Documents/Projetos/fiestasmagicasgalicia"
git -C "$WIN" diff > /tmp/claude.patch
cat /tmp/claude.patch
git -C ~/fiestasmagicasgalicia apply /tmp/claude.patch
```

Para **ficheiros novos** o patch não serve (não estão no índice do git):
copia-os diretamente com `cp`.

**Depois de commitar**, sincroniza a cópia do Windows, senão o agente lê
código velho:

```bash
cd ~/fiestasmagicasgalicia && git push
git -C "$WIN" checkout -- . && git -C "$WIN" pull
```

### 5.3 A shell do David é zsh, e não trata `#` como comentário

Comandos com comentários atrás rebentam (`cat: '#': No such file or
directory`). **Nunca escrever comentários dentro de blocos de comandos**
para ele copiar.

Pela mesma razão, evitar escrever caminhos que a interface transforme em
links — `pool.d/www.conf` chegou-lhe como link markdown e o `rm` não
apanhou o ficheiro. Usar aspas ou reformular.

### 5.4 Arranque em desenvolvimento

```bash
sudo service postgresql start
php artisan test
php artisan fiestas:demo
npm run dev
php artisan serve --host=0.0.0.0 --port=8080
```

`/es` é o site, `/admin` o backoffice.
`bash tools/mostrar-online.sh` abre um túnel temporário para mostrar a
alguém sem servidor — morre quando fechas o script.

## 6. Estado real da máquina (2026-09-08)

Medido, não presumido.

| peça | estado |
|---|---|
| systemd no WSL | `running` (`systemd=true` em `/etc/wsl.conf`) |
| PHP 8.4.25 + bcmath, pgsql, intl, gd, zip | instalado |
| Composer 2.10.3 · Node 22 | instalado |
| PostgreSQL 15 a escutar em `127.0.0.1:5432` | a correr |
| base `fiestas`, utilizador `fiestas`, `btree_gist` + `pgcrypto` | criados |
| nginx | instalado e ativo, **ainda com o site `default`** |
| pool `fiestas` do PHP-FPM | copiado à mão para `/etc/php/8.4/fpm/pool.d/` |
| utilizador `deploy` e `/var/www/fiestas` | criados, pasta **vazia** |
| `cloudflared` + túnel `fiestas` | ativo, ligado a mad05 |
| rota DNS do túnel | **já configurada** desde 2026-09-06 |
| `fail2ban` | falha no WSL (não há iptables a sério). Desligado — sem portas abertas, não faz falta |

**Notas que evitam falsos alarmes:**

- **`/up` a devolver 503 não é avaria.** Verifica o Postgres *e* o heartbeat
  do agendador, e falha em 503 se qualquer um falhar. Com
  `MONITOR_REQUIRE_SCHEDULER=true` e sem `schedule:run`, 503 é a resposta
  correta.
- **`nginx -t` a dar "Permission denied" em `/run/nginx.pid`** é só ter
  corrido sem `sudo`.
- **`pkill -f "artisan serve"` não chega** — o `artisan serve` lança um
  filho `php -S` que sobrevive. Usar `sudo fuser -k 8080/tcp`.

## 7. Deploy — o que já correu de facto

O `deploy/` tem dois runbooks: `README.md` (máquina com IP público) e
`casa.md` (máquina sem IP público — WSL, mini-PC, Raspberry Pi). **É o
`casa.md` que se aplica aqui.**

A hospedagem escolhida é grátis, segura e open source: **Cloudflare Tunnel**
— a ligação sai de dentro para fora, não se abre porta nenhuma no router, e
resolve o CGNAT da fibra portuguesa. Mais fechado do que um VPS com IP
público, não menos.

| passo do `casa.md` | estado |
|---|---|
| 0 — systemd no WSL | feito |
| 1 — `provision.sh` | feito (2026-09-08) |
| 2 — app em `/var/www/fiestas` | **por fazer** |
| 3 — nginx (`site-tunnel.conf`) | **por fazer** |
| 4 — túnel | feito (2026-09-06) |
| 5 — DNS | feito (2026-09-06) |
| 6 — serviços de fundo (systemd) | **por fazer** |
| 7 — confirmar, incluindo o teste dos IPs | **por fazer** |

Duas correções ao `provision.sh` descobertas ao corrê-lo:

- Procura o pool em `/tmp/deploy/php/fiestas-pool.conf`, porque foi escrito
  para um servidor onde o `deploy/` chega por `scp`. Localmente tem de se
  copiar à mão.
- `tools/gen_enums.py` tinha `SRC = 'schema.sql'`, caminho que não existe a
  partir da raiz. Corrigido para `database/schema/schema.sql`.

**O que isto não é.** O PC do David não é um servidor: adormece com o
Windows, reinicia para atualizações e vai abaixo com a luz. Serve para ver o
site no ar, mostrar à Sol e exercitar o runbook. **Não serve para receber
pedidos de clientes a sério.**

## 8. Pendentes, por ordem

1. **CI vermelho.** 258 testes passam, **1 falha**:
   `tests/Feature/QuoteFlowTest.php:128` — *"a reserva inclui as folgas de
   montagem e limpeza da peça"*. Já verificado: o `$fillable` do `Item`
   inclui `buffer_before_min`/`buffer_after_min` e o `Period::padded` está
   correto, portanto a causa está entre o `Event::occupancyWindow()` e o
   período que fica gravado na reserva. **O CI nunca passou** — as 7
   execuções falharam. Os "222 testes verdes" dos docs antigos vinham só de
   corridas locais.
2. **Terminar o deploy**: passos 2, 3, 6 e 7 do `casa.md`.
3. **Bio da Sol** — `lang/*/site.php`, chave `about.placeholder`. É texto,
   escreve-o ela, com as palavras dela.
4. **Serviços e catálogo por publicar.** O *"Estamos a preparar esta
   secção"* **não é placeholder de texto** — é o estado vazio que aparece
   por não haver `Service` publicados. Corrige-se com o `CatalogSeeder` ou
   criando-os no `/admin`.
5. **9 fotos do portefólio em rascunho** — rever, traduzir legendas,
   confirmar consentimento por cliente, e só depois publicar.
6. **Email por configurar** (`MAIL_MAILER=log`). Orçamentos e lembretes não
   saem para ninguém, só ficam em log.
7. **Stripe por configurar.** O `StripeGateway` nunca correu contra a
   Stripe, nem em modo de teste. Sem chave, tudo usa a passarela falsa.
   Alternativa que o backoffice já suporta: transferência ou pagamento em
   mão.
8. **Monitorização externa.** Falta alguém de fora a bater no `/up` —
   UptimeRobot, Better Stack ou healthchecks.io. Tem de ser de fora: um
   processo na mesma máquina cala-se com ela.
9. **PHPStan/Larastan** por correr localmente.

Adiados de propósito: **Newsletter** (tabela existe, sem SMTP é código a
dormir) e **Fase 4, Veri\*factu** (a empresa não está formalizada; a
obrigação é a 1 de julho de 2027 e construir agora é adivinhar o que a AEAT
vai exigir).

## 9. Quem deve o quê

| | De quem |
|---|---|
| Servidor, Stripe, SMTP | David |
| Preços, horário, % de sinal, antecedência mínima | Sol |
| Texto do "quiénes somos" e de 2–3 zonas | Sol |
| NIF e morada (as páginas legais estão despublicadas por falta deles) | Sol |
| Fotos reais e consentimentos | Sol |

**A conversar com a Sol, não a decidir sozinho:** se o percurso do
backoffice bate certo com a ordem em que ela trabalha hoje, e o que ela quer
que uma cliente veja na página de um trabalho. A página *"Cómo se usa esto"*
dentro do `/admin` serve de base a essa conversa.

## 10. Erros já cometidos — não repetir

- **Acreditar nos docs sem verificar.** O `docs/estado-do-projeto.md` dizia
  que o galego estava atrasado (estava traduzido por inteiro) e que não
  havia deploy (havia, com 8 passos). O `docs/pendente.md` dizia que o
  `deploy/` "nunca correu numa máquina" quando os passos 4 e 5 já tinham
  corrido.
- **Assumir o stack.** MariaDB e PHP 8.3 foram assumidos a partir de um doc
  desatualizado. São Postgres e PHP 8.4.
- **Tratar o `/up` a 503 como avaria.** É por design.
- **Editar `lang/*.php` sem editar o gerador.** A correção seguinte apaga o
  trabalho.
- **Mandar comandos com comentários `#`** para uma shell zsh.

## 11. Onde está o resto

| ficheiro | o que tem |
|---|---|
| `README.md` | arranque, organização das pastas, decisões de fundo |
| `docs/pendente.md` | regras de negócio em detalhe, monitorização, o que nunca foi exercido |
| `docs/perguntas-sol.md` | o que falta perguntar à dona |
| `docs/marketing.md` | posicionamento e SEO |
| `docs/redirecoes.md` | como funcionam os 301 |
| `deploy/README.md` | runbook para máquina com IP público |
| `deploy/casa.md` | runbook para máquina sem IP público — **é este** |
| `database/schema/README.md` | como mexer no esquema |
| `tools/README.md` | os geradores (atenção: diz que todos escrevem para `build/`, e não é verdade) |
