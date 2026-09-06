# Contexto e pendentes

Para quem pegar nisto a seguir — pessoa ou agente. **Lê as regras antes de
mexer**: quase todas custaram um erro a descobrir, e metade vive no `CHECK`
da base de dados e não no PHP.

## O que isto é

Site público + backoffice para uma empresa de decoração de festas em
Sabarís, Baiona (Galiza). Dona: **Sol Fernández**. Três mercados —
espanhol, galego, português — não são traduções de cortesia.

Laravel 13.30 · PHP 8.4 · PostgreSQL 16 · Livewire 4 · Filament 5 ·
Tailwind 4 · Vite 8 · Pest 4.7 · spatie/laravel-translatable ·
spatie/laravel-permission

O percurso completo faz-se sem tocar em código: pedido do site → cliente e
evento → projeto → orçamento → enviar → cliente aceita (material fica
reservado) → sinal pago.

## Arranque

```bash
sudo service postgresql start      # se os testes derem "Connection refused"
php artisan test                   # 222 testes; tem de estar tudo verde ANTES de mexer
php artisan fiestas:demo           # dados de exemplo (--limpiar apaga)
npm run dev                        # num terminal
php artisan serve --host=0.0.0.0 --port=8080
```

`/es` é o site, `/admin` o backoffice. `bash tools/mostrar-online.sh` abre
um túnel Cloudflare com endereço HTTPS público, para mostrar a alguém sem
ter servidor.

## Regras que não se mexem

1. **`database/schema/schema.sql` é a fonte de verdade.** Migrações, enums e
   ficheiros de idioma são gerados a partir dele pelos scripts em `tools/`.
   Não editar enums nem `lang/*.php` à mão — editar o gerador e correr outra
   vez.
2. **Nenhuma frase visível dentro de uma view.** Tudo em
   `lang/{es,gl,pt}/`. Os geradores falham, e não escrevem nada, se faltar
   uma chave num idioma.
3. **O stock é protegido por um trigger com advisory lock**, não por uma
   verificação em PHP. Duas pessoas ao mesmo tempo contornam o PHP.
4. **Um orçamento enviado não se edita** — cria-se a versão seguinte
   (`QuoteBuilder::reviseFrom`). É a única prova do que a cliente viu quando
   aceitou.
5. **Nunca se dá um pagamento por pago porque alguém abriu uma URL.** Quem
   confirma é o fornecedor, perguntando-lhe. Vale para a volta do browser e
   para o `payments:reconcile`; os dois passam pelo `SettlePayment`.
6. **Nada se publica sem consentimento**: trabalho sem `consent_at`, opinião
   sem consentimento datado, zona sem 200 caracteres de texto próprio. Os
   três são `CHECK` na base de dados.
7. **Não se inventam dados.** Preço a zero mostra "a consultar". Há testes
   que falham se alguém inventar testemunhos ou preços.
8. **Um link que não vai a lado nenhum não se escreve** — fica texto
   simples, nunca `href="#"`.
9. **`trustProxies` é `['127.0.0.1','::1']`**, não `'*'`. Atrás da
   Cloudflare o `X-Forwarded-For` é forjável; quem decide o IP é o nginx a
   partir do `CF-Connecting-IP`. Mexer nisto parte o rate-limit do
   formulário e o `hashIp()` dos leads.
10. **Um projeto (`event_designs`) não reserva material.** Reservar é do
    orçamento aceite.

## Estado, pelo plano

| | Estado |
|---|---|
| Fase 1 — site público, 3 idiomas, SEO, PWA, formulário + RGPD | feito |
| Fase 2 — 7· orçamentos · 8· aprovação com reserva atómica · 9· sinal | feito |
| Fase 2b — backoffice Filament, painel, guia da Sol | feito |
| Fase 3 — aluguer, SEO local, portefólio, serviços, conteúdo, projetos, agendador | feito |
| Fase 4 — faturação legal Veri\*factu | adiado |
| Newsletter | adiado |
| Monitorização | código feito; falta o serviço de fora a bater no `/up` |

222 testes (Pest), 42 testes de regras de negócio em SQL puro, e um de
concorrência com duas threads a disputar a última unidade de stock.

## Monitorização

O `/up` deixou de responder 200 só por a aplicação ter arrancado. Agora
verifica duas coisas, e as duas falham em 503:

1. **O Postgres responde.** Sem isto, com a base de dados em baixo o site
   dava erro em todas as páginas e o `/up` continuava verde — um alarme
   desligado com aspeto de alarme ligado.
2. **O agendador deu sinal há menos de 15 minutos.** O `monitor:heartbeat`
   escreve a hora de cinco em cinco minutos e não faz mais nada; é essa a
   graça. Se parar de escrever é porque o `schedule:run` deixou de ser
   chamado, e isso é a falha mais cara do sistema porque é silenciosa: as
   reservas de carrinho não voltam ao stock, os orçamentos ficam
   eternamente "enviados", o lembrete da festa não sai e os pagamentos por
   confirmar ficam por confirmar. Um pinger que só olha para o site nunca
   apanharia isto.

A segunda verificação só corre com `MONITOR_REQUIRE_SCHEDULER=true`. Em
desenvolvimento fica desligada de propósito: um `/up` sempre vermelho na
máquina de quem programa ensina a ignorar o `/up`.

```env
MONITOR_REQUIRE_SCHEDULER=true      # só em produção
MONITOR_SCHEDULER_TOLERANCE=15      # minutos
```

Os quatro comandos agendados escrevem `Log::critical` quando falham. Não
é um aviso a sério — é o mínimo para o `journalctl -u fiestas-scheduler`
dizer alguma coisa no dia em que alguém for lá ver.

**O que falta, e não é código:** alguém de fora a bater no `/up`. Tem de
ser de fora — um processo na mesma máquina cala-se com ela. Um serviço
gratuito (UptimeRobot, Better Stack, healthchecks.io) a cada minuto,
com aviso por email e SMS, resolve. Só se pode fazer depois de haver
servidor e domínio, porque precisa de uma URL pública estável.

Adiados de propósito, e a razão importa:

- **Newsletter** — a tabela `newsletter_subscribers` já existe. Sem SMTP não
  envia nada: é código a dormir.
- **Fase 4, Veri\*factu** — a cadeia de hash está preparada
  (`documents`, `document_series`). A empresa não está formalizada e a
  obrigação para autónomos é a 1 de julho de 2027. Construir agora é
  adivinhar o que a AEAT vai exigir.

## Escrito mas nunca exercido

Não é código por fazer — é código que nunca correu a sério. Não o dês por
provado.

- **`StripeGateway`** nunca correu contra a Stripe, nem em modo de teste.
  Sem chave em `services.stripe.secret`, tudo usa a passarela falsa (e em
  produção fica um aviso no log). A biblioteca já está instalada
  (`stripe/stripe-php`); faltam a conta e as chaves. Alternativa: cobrar por
  transferência ou em mão, que o backoffice já suporta.
- **`deploy/`** — runbook de 8 passos para um Debian atrás da Cloudflare.
  Nunca correu numa máquina.
- **A atomicidade da reserva** está provada por um teste de 2 threads, não
  por carga real.

## Falta de pessoas

| | De quem |
|---|---|
| Servidor | David |
| Conta Stripe · SMTP · remoto de git (para o CI correr) | David |
| Preços, horário, % de sinal, antecedência mínima | Sol |
| Texto do "quiénes somos" e de 2–3 zonas | Sol |
| NIF e morada (as legais estão PENDIENTE e despublicadas) | Sol |
| Fotos reais (Trabajos → Fotos) | Sol |

## A ver com a Sol, não a decidir sozinho

Se o percurso do backoffice bate certo com a ordem em que ela trabalha
hoje, e o que ela quer que uma cliente veja na página de um trabalho. A
página **"Cómo se usa esto"** dentro do `/admin` serve de base a essa
conversa.
