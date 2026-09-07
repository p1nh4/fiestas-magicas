# Estado do projeto — Fiestas Mágicas / DecorArte

Gerado em 2026-09-07, pra retomar amanhã. Nota geral: **7.5/10** — a base técnica é sólida (arquitetura, testes, segurança, fluxo de negócio real), o que falta é sobretudo conteúdo real, configuração de serviços externos e hospedagem, não código.

## O que já tem

**Site público** (es/gl/pt): home, portfólio (trabalhos), serviços, zonas por concelho (SEO local com hreflang), formulário de orçamento (lead).

**Backoffice (Filament)** — 14 recursos administrativos: Clients, Events, EventDesigns, Faqs, Items (catálogo/inventário), Leads, Pages (CMS das páginas legais), Payments, Projects (portfólio), Quotes, Redirects (301 pra SEO), Reservations, ServiceAreas, Services, Testimonials.

**Fluxo de negócio real, ponta a ponta**: Lead → Event → EventDesign (mood board/orçamento visual) → Quote (com controlo de versões) → aceitação com lock de concorrência (não reserva material a dobrar) → Payment via Stripe → reserva de material com folgas de montagem/transporte incluídas.

**Segurança**: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS em produção, e agora CSP a bloquear de vez (testado em produção simulada e em `npm run dev`, zero violações).

**RGPD**: páginas legais (aviso legal/privacidade/cookies) como conteúdo editável no admin (`Page` model + `LegalPagesSeeder` com texto real, não placeholder) — falta só confirmar que o seeder já rodou no teu ambiente real. Consentimento do cliente obrigatório antes de publicar fotos de uma festa (constraint no próprio banco de dados, não dá pra contornar sem querer).

**Testes**: 259 testes. No meu sandbox 214 passam e 45 falham só por faltar a extensão `bcmath` no ambiente de teste daqui (confirmado pelo próprio CI, que exige bcmath) — não é bug real, é limitação do meu sandbox.

**CI**: `.github/workflows/ci.yml` configurado.

**Hoje**: comando de importação das fotos reais pro portfólio (9 rascunhos aguardando revisão/tradução/consentimento), cores atualizadas pra bater com o cartão da marca, CSP ativado e corrigido.

## O que falta / pontos de atenção

1. **Conteúdo real ainda é placeholder** em dois pontos: a bio da Sol ("Quiénes somos") e a seção "Lo que montamos" (hoje mostra "Estamos preparando esta sección"). Isso é texto, não código — é a Sol quem escreve, com as próprias palavras.
2. **Tradução em galego (gl) muito atrás** de espanhol e português — bem menos chaves preenchidas nos ficheiros de idioma. Precisa de uma passada de tradução.
3. **Stripe não configurado**: `.env.example` tem as chaves vazias — falta criar/ligar a conta Stripe real antes de aceitar pagamento de verdade.
4. **Email ainda não configurado**: `MAIL_MAILER=log` — os orçamentos e lembretes de evento não estão a ser enviados de verdade pra ninguém, só gravados em log.
5. **Sem hospedagem/deploy definidos** — nem Dockerfile nem pipeline de deploy no repositório. É a pergunta que tu levantaste agora: onde isto vai correr (Android, portátil velho, etc.) ainda está em aberto.
6. **9 fotos importadas em rascunho** — reveste, traduz, confirma consentimento por cliente e só depois publica no admin.
7. **PHPStan/Larastan não rodou** nesta sessão (bloqueio de rede só no meu sandbox) — vale correr localmente pra confirmar que está tudo limpo.

## Amanhã

A ideia combinada: ir módulo a módulo, tu mais à mão no código, eu a ajudar — e decidir junto onde isto vai ficar online (Android/iPhone/portátil) antes de ir mais longe com conteúdo e Stripe/email.
