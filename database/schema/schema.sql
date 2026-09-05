-- =============================================================================
--  DecorArte · Fiestas Mágicas — esquema de referência (PostgreSQL 16+)
--
--  Este ficheiro é a FONTE DE VERDADE do modelo de dados. As migrations do
--  Laravel espelham-no. Existe separado para poder ser corrido e testado
--  contra um Postgres real, sem depender do framework.
--
--  Convenções:
--   · id bigserial  → chave interna;  uuid → identificador público (URLs)
--   · conteúdo traduzível em jsonb  {"es": "...", "gl": "...", "pt": "..."}
--   · estados em varchar + CHECK (espelhados por enums PHP), não enums PG,
--     porque acrescentar um valor a um enum PG é uma migration bloqueante
--   · dinheiro em numeric(10,2); nunca float
--   · todos os instantes em timestamptz (a empresa é ES, os clientes podem ser PT)
-- =============================================================================

BEGIN;

-- @@SLICE core
CREATE EXTENSION IF NOT EXISTS btree_gist;   -- gist sobre (bigint, tstzrange)

-- =============================================================================
--  1. PESSOAS
-- =============================================================================

-- @@SLICE users
-- Equipa interna (autentica no backoffice). Papéis via spatie/laravel-permission.
CREATE TABLE users (
    id                  bigserial PRIMARY KEY,
    uuid                uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    name                varchar(120) NOT NULL,
    email               varchar(180) NOT NULL UNIQUE,
    email_verified_at   timestamptz,
    password            varchar(255) NOT NULL,
    locale              varchar(5) NOT NULL DEFAULT 'es',
    is_active           boolean NOT NULL DEFAULT true,
    last_login_at       timestamptz,
    last_login_ip       inet,
    remember_token      varchar(100),
    created_at          timestamptz NOT NULL DEFAULT now(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT users_locale_chk CHECK (locale IN ('es','gl','pt'))
);

-- Tabelas que o próprio Laravel exige (auth e sessões).
CREATE TABLE password_reset_tokens (
    email       varchar(180) PRIMARY KEY,
    token       varchar(255) NOT NULL,
    created_at  timestamptz
);

CREATE TABLE sessions (
    id              varchar(255) PRIMARY KEY,
    user_id         bigint REFERENCES users(id) ON DELETE SET NULL,
    ip_address      varchar(45),
    user_agent      text,
    payload         text NOT NULL,
    last_activity   int NOT NULL
);
CREATE INDEX sessions_user_id_idx       ON sessions (user_id);
CREATE INDEX sessions_last_activity_idx ON sessions (last_activity);

-- @@SLICE clients
-- Clientes finais. Podem autenticar na área de cliente por link mágico.
CREATE TABLE clients (
    id                  bigserial PRIMARY KEY,
    uuid                uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    kind                varchar(12) NOT NULL DEFAULT 'person',
    name                varchar(160) NOT NULL,
    legal_name          varchar(200),
    tax_id              varchar(32),                    -- NIF / NIE / CIF / NIF-PT
    email               varchar(180),
    phone               varchar(32),
    whatsapp            varchar(32),
    locale              varchar(5) NOT NULL DEFAULT 'es',
    address_line        varchar(200),
    postal_code         varchar(16),
    city                varchar(120),
    province            varchar(120),
    country             char(2) NOT NULL DEFAULT 'ES',
    source              varchar(24),                    -- instagram, facebook, web, boca_a_boca...
    notes               text,
    marketing_opt_in_at timestamptz,                    -- RGPD: prova de consentimento
    created_at          timestamptz NOT NULL DEFAULT now(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    deleted_at          timestamptz,
    CONSTRAINT clients_kind_chk   CHECK (kind IN ('person','company')),
    CONSTRAINT clients_locale_chk CHECK (locale IN ('es','gl','pt')),
    CONSTRAINT clients_reach_chk  CHECK (email IS NOT NULL OR phone IS NOT NULL)
);
CREATE INDEX clients_name_idx  ON clients (lower(name));
CREATE INDEX clients_email_idx ON clients (lower(email)) WHERE email IS NOT NULL;
CREATE INDEX clients_phone_idx ON clients (phone) WHERE phone IS NOT NULL;

-- =============================================================================
--  2. CATÁLOGO
-- =============================================================================

-- @@SLICE catalog
CREATE TABLE categories (
    id          bigserial PRIMARY KEY,
    kind        varchar(12) NOT NULL,               -- 'service' | 'item'
    name        jsonb NOT NULL DEFAULT '{}'::jsonb,
    slug        jsonb NOT NULL DEFAULT '{}'::jsonb,
    position    int NOT NULL DEFAULT 0,
    is_active   boolean NOT NULL DEFAULT true,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT categories_kind_chk CHECK (kind IN ('service','item'))
);
CREATE UNIQUE INDEX categories_slug_es_uq ON categories ((slug->>'es'), kind);
CREATE UNIQUE INDEX categories_slug_gl_uq ON categories ((slug->>'gl'), kind);
CREATE UNIQUE INDEX categories_slug_pt_uq ON categories ((slug->>'pt'), kind);

-- O que a empresa faz (montagem incluída).
CREATE TABLE services (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    category_id     bigint REFERENCES categories(id) ON DELETE SET NULL,
    name            jsonb NOT NULL DEFAULT '{}'::jsonb,
    slug            jsonb NOT NULL DEFAULT '{}'::jsonb,
    summary         jsonb NOT NULL DEFAULT '{}'::jsonb,
    description     jsonb NOT NULL DEFAULT '{}'::jsonb,
    base_price      numeric(10,2) NOT NULL DEFAULT 0 CHECK (base_price >= 0),
    price_mode      varchar(16) NOT NULL DEFAULT 'fixed',
    setup_minutes   int NOT NULL DEFAULT 0 CHECK (setup_minutes >= 0),
    is_active       boolean NOT NULL DEFAULT true,
    position        int NOT NULL DEFAULT 0,
    seo             jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT services_price_mode_chk CHECK (price_mode IN ('fixed','per_guest','per_hour','quote'))
);
CREATE UNIQUE INDEX services_slug_es_uq ON services ((slug->>'es'));
CREATE UNIQUE INDEX services_slug_gl_uq ON services ((slug->>'gl'));
CREATE UNIQUE INDEX services_slug_pt_uq ON services ((slug->>'pt'));

-- Peças físicas com stock: é aqui que vive o risco de overbooking.
CREATE TABLE items (
    id                  bigserial PRIMARY KEY,
    uuid                uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    category_id         bigint REFERENCES categories(id) ON DELETE SET NULL,
    sku                 varchar(48) NOT NULL UNIQUE,
    name                jsonb NOT NULL DEFAULT '{}'::jsonb,
    slug                jsonb NOT NULL DEFAULT '{}'::jsonb,
    description         jsonb NOT NULL DEFAULT '{}'::jsonb,
    stock_qty           int NOT NULL DEFAULT 1 CHECK (stock_qty >= 0),
    price_per_day       numeric(10,2) NOT NULL DEFAULT 0 CHECK (price_per_day >= 0),
    replacement_value   numeric(10,2) CHECK (replacement_value IS NULL OR replacement_value >= 0),
    -- folga logística em minutos: tempo antes/depois em que a peça não pode
    -- ser alugada (transporte, montagem, limpeza). Em minutos e não em
    -- interval porque o PHP lê um inteiro sem ambiguidade nenhuma.
    buffer_before_min   int NOT NULL DEFAULT 120  CHECK (buffer_before_min >= 0),
    buffer_after_min    int NOT NULL DEFAULT 1440 CHECK (buffer_after_min >= 0),
    requires_transport  boolean NOT NULL DEFAULT false,
    is_rentable         boolean NOT NULL DEFAULT true,   -- aluguer direto ao público
    is_active           boolean NOT NULL DEFAULT true,
    created_at          timestamptz NOT NULL DEFAULT now(),
    updated_at          timestamptz NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX items_slug_es_uq ON items ((slug->>'es'));
CREATE UNIQUE INDEX items_slug_gl_uq ON items ((slug->>'gl'));
CREATE UNIQUE INDEX items_slug_pt_uq ON items ((slug->>'pt'));

-- =============================================================================
--  3. FUNIL COMERCIAL
-- =============================================================================

-- @@SLICE sales
-- Pedido vindo do site. Ainda não é cliente.
CREATE TABLE leads (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    client_id       bigint REFERENCES clients(id) ON DELETE SET NULL,
    name            varchar(160) NOT NULL,
    email           varchar(180),
    phone           varchar(32),
    locale          varchar(5) NOT NULL DEFAULT 'es',
    event_type      varchar(32) NOT NULL,
    event_date      date,
    guests_count    int CHECK (guests_count IS NULL OR guests_count > 0),
    venue           varchar(200),
    budget_hint     varchar(48),
    message         text,
    status          varchar(16) NOT NULL DEFAULT 'new',
    -- marketing: de onde veio, para saber onde investir
    utm_source      varchar(80),
    utm_medium      varchar(80),
    utm_campaign    varchar(120),
    referrer        varchar(400),
    landing_path    varchar(400),
    ip_hash         varchar(64),                    -- IP nunca em claro (RGPD)
    user_agent      varchar(400),
    contacted_at    timestamptz,
    converted_at    timestamptz,
    lost_reason     varchar(120),
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT leads_locale_chk CHECK (locale IN ('es','gl','pt')),
    CONSTRAINT leads_status_chk CHECK (status IN ('new','contacted','quoted','won','lost','spam')),
    CONSTRAINT leads_reach_chk  CHECK (email IS NOT NULL OR phone IS NOT NULL)
);
CREATE INDEX leads_status_idx   ON leads (status, created_at DESC);
CREATE INDEX leads_date_idx     ON leads (event_date) WHERE event_date IS NOT NULL;
CREATE INDEX leads_campaign_idx ON leads (utm_source, utm_campaign) WHERE utm_source IS NOT NULL;

-- A festa em si.
CREATE TABLE events (
    id                  bigserial PRIMARY KEY,
    uuid                uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    client_id           bigint NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    lead_id             bigint REFERENCES leads(id) ON DELETE SET NULL,
    reference           varchar(24) NOT NULL UNIQUE,        -- ex. FM-2026-0031
    title               varchar(200) NOT NULL,
    event_type          varchar(32) NOT NULL,
    status              varchar(16) NOT NULL DEFAULT 'draft',
    starts_at           timestamptz NOT NULL,
    ends_at             timestamptz NOT NULL,
    setup_starts_at     timestamptz,
    teardown_ends_at    timestamptz,
    venue_name          varchar(200),
    venue_address       varchar(300),
    venue_city          varchar(120),
    distance_km         numeric(6,1) CHECK (distance_km IS NULL OR distance_km >= 0),
    guests_count        int CHECK (guests_count IS NULL OR guests_count > 0),
    locale              varchar(5) NOT NULL DEFAULT 'es',
    notes               text,
    internal_notes      text,
    total_amount        numeric(10,2) NOT NULL DEFAULT 0,
    deposit_amount      numeric(10,2) NOT NULL DEFAULT 0,
    paid_amount         numeric(10,2) NOT NULL DEFAULT 0,
    created_at          timestamptz NOT NULL DEFAULT now(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    deleted_at          timestamptz,
    CONSTRAINT events_status_chk CHECK (status IN ('draft','quoted','confirmed','in_progress','done','cancelled')),
    CONSTRAINT events_locale_chk CHECK (locale IN ('es','gl','pt')),
    CONSTRAINT events_window_chk CHECK (ends_at > starts_at),
    CONSTRAINT events_setup_chk  CHECK (setup_starts_at IS NULL OR setup_starts_at <= starts_at),
    CONSTRAINT events_tear_chk   CHECK (teardown_ends_at IS NULL OR teardown_ends_at >= ends_at)
);
CREATE INDEX events_starts_idx ON events (starts_at);
CREATE INDEX events_status_idx ON events (status, starts_at);
CREATE INDEX events_client_idx ON events (client_id);

-- Orçamento. Versionado: nunca se edita um enviado, cria-se a v2.
CREATE TABLE quotes (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    event_id        bigint NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    version         int NOT NULL DEFAULT 1 CHECK (version >= 1),
    status          varchar(16) NOT NULL DEFAULT 'draft',
    public_token    varchar(64) NOT NULL UNIQUE,        -- link mágico para o cliente aprovar
    valid_until     date,
    currency        char(3) NOT NULL DEFAULT 'EUR',
    subtotal        numeric(10,2) NOT NULL DEFAULT 0,
    discount_amount numeric(10,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    tax_rate        numeric(5,2)  NOT NULL DEFAULT 21.00 CHECK (tax_rate >= 0),
    tax_amount      numeric(10,2) NOT NULL DEFAULT 0,
    total           numeric(10,2) NOT NULL DEFAULT 0,
    deposit_pct     numeric(5,2)  NOT NULL DEFAULT 30.00 CHECK (deposit_pct >= 0 AND deposit_pct <= 100),
    notes           jsonb NOT NULL DEFAULT '{}'::jsonb,
    sent_at         timestamptz,
    viewed_at       timestamptz,
    accepted_at     timestamptz,
    accepted_ip_hash varchar(64),
    rejected_at     timestamptz,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT quotes_status_chk CHECK (status IN ('draft','sent','viewed','accepted','rejected','expired')),
    CONSTRAINT quotes_version_uq UNIQUE (event_id, version)
);

CREATE TABLE quote_lines (
    id              bigserial PRIMARY KEY,
    quote_id        bigint NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    -- referência opcional ao catálogo; a descrição fica sempre congelada na linha
    service_id      bigint REFERENCES services(id) ON DELETE SET NULL,
    item_id         bigint REFERENCES items(id) ON DELETE SET NULL,
    description     varchar(300) NOT NULL,
    quantity        numeric(8,2) NOT NULL DEFAULT 1 CHECK (quantity > 0),
    days            int NOT NULL DEFAULT 1 CHECK (days >= 1),
    unit_price      numeric(10,2) NOT NULL DEFAULT 0,
    line_total      numeric(10,2) NOT NULL DEFAULT 0,
    position        int NOT NULL DEFAULT 0,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT quote_lines_source_chk CHECK (NOT (service_id IS NOT NULL AND item_id IS NOT NULL))
);
CREATE INDEX quote_lines_quote_idx ON quote_lines (quote_id, position);

-- =============================================================================
--  4. DISPONIBILIDADE — o coração do sistema
--
--  Uma reserva bloqueia UMA peça durante UM intervalo. `period` já inclui as
--  folgas de montagem/limpeza (buffer_before / buffer_after do item): quem
--  escreve calcula a janela bloqueada, a base de dados garante que ela cabe.
--
--  Peças sem evento associado (event_id NULL) são bloqueios manuais:
--  manutenção, peça partida, férias.
-- =============================================================================

-- @@SLICE reservations
CREATE TABLE reservations (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    item_id         bigint NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    event_id        bigint REFERENCES events(id) ON DELETE CASCADE,
    quantity        int NOT NULL DEFAULT 1 CHECK (quantity > 0),
    period          tstzrange NOT NULL,
    status          varchar(12) NOT NULL DEFAULT 'confirmed',
    blocked_reason  varchar(160),
    hold_expires_at timestamptz,                     -- carrinho: liberta-se sozinho
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT reservations_status_chk CHECK (status IN ('hold','confirmed','cancelled')),
    CONSTRAINT reservations_period_chk CHECK (NOT isempty(period) AND lower(period) IS NOT NULL AND upper(period) IS NOT NULL),
    CONSTRAINT reservations_purpose_chk CHECK (event_id IS NOT NULL OR blocked_reason IS NOT NULL),
    CONSTRAINT reservations_hold_chk CHECK (status <> 'hold' OR hold_expires_at IS NOT NULL)
);

-- gist acelera o teste de sobreposição (&&) por artigo
CREATE INDEX reservations_period_gist
    ON reservations USING gist (item_id, period)
    WHERE status <> 'cancelled';
CREATE INDEX reservations_event_idx ON reservations (event_id) WHERE event_id IS NOT NULL;
CREATE INDEX reservations_hold_idx  ON reservations (hold_expires_at) WHERE status = 'hold';

-- ---------------------------------------------------------------------------
--  Porque é que isto é um TRIGGER e não um EXCLUDE constraint:
--
--  Um EXCLUDE resolve "esta peça única não pode sair duas vezes ao mesmo
--  tempo". Aqui há stock agregado (40 cadeiras), e é preciso somar as
--  reservas sobrepostas e comparar com o stock — o EXCLUDE não faz somas.
--
--  Algoritmo (varrimento de linha): o pico de procura de um intervalo
--  só pode acontecer no INÍCIO de alguma reserva. Basta então testar os
--  inícios das reservas que se sobrepõem à nova e ver, em cada um, quanto
--  está simultaneamente reservado.
--
--  O advisory lock por artigo serializa duas transações concorrentes que
--  reservem a mesma peça — sem ele, ambas leriam "há stock" e ambas
--  escreveriam (write skew).
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION reservations_check_capacity() RETURNS trigger
LANGUAGE plpgsql AS $fn$
DECLARE
    v_stock int;
    v_peak  int;
    v_at    timestamptz;
BEGIN
    IF NEW.status = 'cancelled' THEN
        RETURN NULL;
    END IF;

    PERFORM pg_advisory_xact_lock(hashtext('reservations'), NEW.item_id::int);

    SELECT stock_qty INTO v_stock FROM items WHERE id = NEW.item_id;
    IF v_stock IS NULL THEN
        RAISE EXCEPTION 'Artigo % não existe', NEW.item_id USING ERRCODE = '23503';
    END IF;

    SELECT s.demand, s.ts INTO v_peak, v_at
    FROM (
        SELECT b.ts, SUM(r.quantity)::int AS demand
        FROM (
            SELECT DISTINCT lower(period) AS ts
            FROM reservations
            WHERE item_id = NEW.item_id
              AND status <> 'cancelled'
              AND period && NEW.period
        ) b
        JOIN reservations r
          ON r.item_id = NEW.item_id
         AND r.status <> 'cancelled'
         AND r.period @> b.ts
        GROUP BY b.ts
        ORDER BY 2 DESC
        LIMIT 1
    ) s;

    IF v_peak > v_stock THEN
        RAISE EXCEPTION
            'Stock insuficiente no artigo % em %: pedidas % unidades, existem %',
            NEW.item_id, v_at, v_peak, v_stock
            USING ERRCODE = '23514';
    END IF;

    RETURN NULL;
END
$fn$;

CREATE CONSTRAINT TRIGGER reservations_capacity
    AFTER INSERT OR UPDATE ON reservations
    DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW EXECUTE FUNCTION reservations_check_capacity();

-- Baixar o stock de um artigo também pode criar overbooking: revalidar.
CREATE OR REPLACE FUNCTION items_recheck_capacity() RETURNS trigger
LANGUAGE plpgsql AS $fn$
DECLARE
    v_peak int;
    v_at   timestamptz;
BEGIN
    IF NEW.stock_qty >= OLD.stock_qty THEN
        RETURN NULL;
    END IF;

    SELECT s.demand, s.ts INTO v_peak, v_at
    FROM (
        SELECT lower(r.period) AS ts,
               (SELECT SUM(r2.quantity)::int
                  FROM reservations r2
                 WHERE r2.item_id = NEW.id
                   AND r2.status <> 'cancelled'
                   AND r2.period @> lower(r.period)) AS demand
        FROM reservations r
        WHERE r.item_id = NEW.id
          AND r.status <> 'cancelled'
          AND upper(r.period) > now()
        ORDER BY 2 DESC
        LIMIT 1
    ) s;

    IF v_peak IS NOT NULL AND v_peak > NEW.stock_qty THEN
        RAISE EXCEPTION
            'Não dá para baixar o stock de % para %: em % já há % unidades reservadas',
            NEW.id, NEW.stock_qty, v_at, v_peak
            USING ERRCODE = '23514';
    END IF;

    RETURN NULL;
END
$fn$;

CREATE CONSTRAINT TRIGGER items_stock_guard
    AFTER UPDATE ON items
    DEFERRABLE INITIALLY IMMEDIATE
    FOR EACH ROW EXECUTE FUNCTION items_recheck_capacity();

-- =============================================================================
--  5. DINHEIRO
-- =============================================================================

-- @@SLICE billing
CREATE TABLE payments (
    id                  bigserial PRIMARY KEY,
    uuid                uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    event_id            bigint REFERENCES events(id) ON DELETE SET NULL,
    client_id           bigint REFERENCES clients(id) ON DELETE SET NULL,
    kind                varchar(12) NOT NULL DEFAULT 'deposit',
    method              varchar(16) NOT NULL,
    status              varchar(12) NOT NULL DEFAULT 'pending',
    amount              numeric(10,2) NOT NULL CHECK (amount <> 0),
    currency            char(3) NOT NULL DEFAULT 'EUR',
    provider            varchar(24),                    -- stripe, manual...
    provider_reference  varchar(120),
    paid_at             timestamptz,
    meta                jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at          timestamptz NOT NULL DEFAULT now(),
    updated_at          timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT payments_kind_chk   CHECK (kind IN ('deposit','balance','extra','refund')),
    CONSTRAINT payments_method_chk CHECK (method IN ('card','bizum','transfer','cash','other')),
    CONSTRAINT payments_status_chk CHECK (status IN ('pending','paid','failed','refunded')),
    CONSTRAINT payments_refund_chk CHECK ((kind = 'refund') = (amount < 0))
);
CREATE UNIQUE INDEX payments_provider_ref_uq
    ON payments (provider, provider_reference)
    WHERE provider_reference IS NOT NULL;
CREATE INDEX payments_event_idx ON payments (event_id);

-- Séries de numeração. Separadas por tipo e ano — exigência espanhola.
CREATE TABLE document_series (
    id              bigserial PRIMARY KEY,
    code            varchar(16) NOT NULL,
    doc_type        varchar(16) NOT NULL,
    prefix          varchar(16) NOT NULL DEFAULT '',
    year            int NOT NULL,
    next_sequence   int NOT NULL DEFAULT 1 CHECK (next_sequence >= 1),
    is_active       boolean NOT NULL DEFAULT true,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT document_series_type_chk CHECK (doc_type IN ('quote','proforma','receipt','invoice','credit_note')),
    CONSTRAINT document_series_uq UNIQUE (code, year)
);

-- ---------------------------------------------------------------------------
--  Documentos emitidos. A "ponta instalada" para Veri*factu:
--  numeração sequencial sem buracos, encadeamento por hash e imutabilidade
--  depois de selado. Hoje serve para orçamentos e recibos; quando a empresa
--  se formalizar (autónomos: 1 de julho de 2027) já não é preciso mexer aqui.
-- ---------------------------------------------------------------------------
CREATE TABLE documents (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    series_id       bigint NOT NULL REFERENCES document_series(id) ON DELETE RESTRICT,
    sequence        int NOT NULL CHECK (sequence >= 1),
    number          varchar(48) NOT NULL,
    doc_type        varchar(16) NOT NULL,
    event_id        bigint REFERENCES events(id) ON DELETE SET NULL,
    client_id       bigint REFERENCES clients(id) ON DELETE RESTRICT,
    quote_id        bigint REFERENCES quotes(id) ON DELETE SET NULL,
    issued_at       timestamptz NOT NULL DEFAULT now(),
    currency        char(3) NOT NULL DEFAULT 'EUR',
    subtotal        numeric(10,2) NOT NULL DEFAULT 0,
    tax_rate        numeric(5,2)  NOT NULL DEFAULT 21.00,
    tax_amount      numeric(10,2) NOT NULL DEFAULT 0,
    total           numeric(10,2) NOT NULL DEFAULT 0,
    -- instantâneo dos dados fiscais no momento da emissão (não podem mudar depois)
    snapshot        jsonb NOT NULL DEFAULT '{}'::jsonb,
    pdf_path        varchar(300),
    -- encadeamento
    chain_index     int,
    content_hash    char(64),
    previous_hash   char(64),
    locked_at       timestamptz,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT documents_type_chk CHECK (doc_type IN ('quote','proforma','receipt','invoice','credit_note')),
    CONSTRAINT documents_seq_uq   UNIQUE (series_id, sequence),
    CONSTRAINT documents_num_uq   UNIQUE (number),
    CONSTRAINT documents_lock_chk CHECK (locked_at IS NULL OR content_hash IS NOT NULL)
);
CREATE INDEX documents_event_idx  ON documents (event_id);
CREATE INDEX documents_issued_idx ON documents (issued_at DESC);

-- Depois de selado, um documento não se altera nem se apaga. Nem pela app.
CREATE OR REPLACE FUNCTION documents_immutability() RETURNS trigger
LANGUAGE plpgsql AS $fn$
BEGIN
    IF TG_OP = 'DELETE' THEN
        IF OLD.locked_at IS NOT NULL THEN
            RAISE EXCEPTION 'O documento % está selado e não pode ser apagado', OLD.number
                USING ERRCODE = '23514';
        END IF;
        RETURN OLD;
    END IF;

    IF OLD.locked_at IS NOT NULL THEN
        RAISE EXCEPTION 'O documento % está selado e não pode ser alterado', OLD.number
            USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END
$fn$;

CREATE TRIGGER documents_immutable
    BEFORE UPDATE OR DELETE ON documents
    FOR EACH ROW EXECUTE FUNCTION documents_immutability();

-- =============================================================================
--  6. CONTEÚDO / MARKETING
-- =============================================================================

-- @@SLICE content
-- Trabalhos publicados. É o portefólio, e a principal arma de SEO.
CREATE TABLE projects (
    id              bigserial PRIMARY KEY,
    uuid            uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    event_id        bigint REFERENCES events(id) ON DELETE SET NULL,
    title           jsonb NOT NULL DEFAULT '{}'::jsonb,
    slug            jsonb NOT NULL DEFAULT '{}'::jsonb,
    description     jsonb NOT NULL DEFAULT '{}'::jsonb,
    event_type      varchar(32) NOT NULL,
    happened_on     date,
    venue           varchar(200),
    city            varchar(120),
    guests_count    int,
    is_featured     boolean NOT NULL DEFAULT false,
    is_published    boolean NOT NULL DEFAULT false,
    published_at    timestamptz,
    position        int NOT NULL DEFAULT 0,
    seo             jsonb NOT NULL DEFAULT '{}'::jsonb,
    -- consentimento explícito do cliente para publicar as fotos da sua festa
    consent_at      timestamptz,
    created_at      timestamptz NOT NULL DEFAULT now(),
    updated_at      timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT projects_publish_chk CHECK (NOT is_published OR consent_at IS NOT NULL)
);
CREATE UNIQUE INDEX projects_slug_es_uq ON projects ((slug->>'es'));
CREATE UNIQUE INDEX projects_slug_gl_uq ON projects ((slug->>'gl'));
CREATE UNIQUE INDEX projects_slug_pt_uq ON projects ((slug->>'pt'));
CREATE INDEX projects_pub_idx ON projects (is_published, published_at DESC);

CREATE TABLE pages (
    id           bigserial PRIMARY KEY,
    key          varchar(48) NOT NULL UNIQUE,
    title        jsonb NOT NULL DEFAULT '{}'::jsonb,
    slug         jsonb NOT NULL DEFAULT '{}'::jsonb,
    body         jsonb NOT NULL DEFAULT '{}'::jsonb,
    seo          jsonb NOT NULL DEFAULT '{}'::jsonb,
    is_published boolean NOT NULL DEFAULT false,
    created_at   timestamptz NOT NULL DEFAULT now(),
    updated_at   timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE faqs (
    id           bigserial PRIMARY KEY,
    question     jsonb NOT NULL DEFAULT '{}'::jsonb,
    answer       jsonb NOT NULL DEFAULT '{}'::jsonb,
    position     int NOT NULL DEFAULT 0,
    is_published boolean NOT NULL DEFAULT true,
    created_at   timestamptz NOT NULL DEFAULT now(),
    updated_at   timestamptz NOT NULL DEFAULT now()
);

-- Testemunhos REAIS, com autorização. Nunca preenchidos com texto inventado.
CREATE TABLE testimonials (
    id            bigserial PRIMARY KEY,
    client_id     bigint REFERENCES clients(id) ON DELETE SET NULL,
    event_id      bigint REFERENCES events(id) ON DELETE SET NULL,
    author_name   varchar(120) NOT NULL,
    body          text NOT NULL,
    locale        varchar(5) NOT NULL DEFAULT 'es',
    rating        int CHECK (rating IS NULL OR rating BETWEEN 1 AND 5),
    source        varchar(24),                     -- google, instagram, email
    source_url    varchar(400),
    consent_at    timestamptz NOT NULL,            -- sem autorização não entra
    is_published  boolean NOT NULL DEFAULT false,
    created_at    timestamptz NOT NULL DEFAULT now(),
    updated_at    timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT testimonials_locale_chk CHECK (locale IN ('es','gl','pt'))
);

-- Redirects: quando um slug muda, o SEO não se perde.
CREATE TABLE redirects (
    id          bigserial PRIMARY KEY,
    from_path   varchar(400) NOT NULL UNIQUE,
    to_path     varchar(400) NOT NULL,
    status_code int NOT NULL DEFAULT 301 CHECK (status_code IN (301,302,307,308)),
    hits        bigint NOT NULL DEFAULT 0,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE newsletter_subscribers (
    id            bigserial PRIMARY KEY,
    email         varchar(180) NOT NULL UNIQUE,
    locale        varchar(5) NOT NULL DEFAULT 'es',
    token         varchar(64) NOT NULL UNIQUE,
    confirmed_at  timestamptz,                     -- double opt-in (RGPD)
    unsubscribed_at timestamptz,
    created_at    timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT newsletter_locale_chk CHECK (locale IN ('es','gl','pt'))
);

-- @@SLICE local_seo
-- ---------------------------------------------------------------------------
--  Zonas de trabalho.
--
--  A empresa está em Baiona e desloca-se pelo Val Miñor, Baixo Miño, Vigo e
--  norte de Portugal. Quem procura no Google não escreve "decoración de
--  fiestas": escreve "decoración de globos en Nigrán". Uma página por
--  concelho é a forma de aparecer nessas buscas.
--
--  E é também a forma mais fácil de estragar o site inteiro. Onze páginas
--  iguais com o nome do sítio trocado chamam-se doorway pages, e o Google
--  não as ignora — desvaloriza o domínio TODO por causa delas. Ou cada
--  página diz alguma coisa que só é verdade naquele sítio, ou não deve
--  existir.
--
--  Daí o CHECK: uma zona não se publica sem texto próprio escrito. Não é
--  uma sugestão na interface, que se contorna; é a base de dados a recusar
--  a linha. Os 200 caracteres não são uma medida de qualidade — são o
--  mínimo abaixo do qual é obviamente texto de encher.
-- ---------------------------------------------------------------------------
CREATE TABLE service_areas (
    id             bigserial PRIMARY KEY,
    uuid           uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    name           varchar(120) NOT NULL UNIQUE,
    slug           jsonb NOT NULL DEFAULT '{}'::jsonb,
    province       varchar(120),
    country        char(2) NOT NULL DEFAULT 'ES',
    distance_km    numeric(6,1) CHECK (distance_km IS NULL OR distance_km >= 0),
    travel_minutes int CHECK (travel_minutes IS NULL OR travel_minutes >= 0),
    -- Texto próprio da zona, escrito pela Sol. É o que justifica a página.
    intro          jsonb NOT NULL DEFAULT '{}'::jsonb,
    seo            jsonb NOT NULL DEFAULT '{}'::jsonb,
    position       int NOT NULL DEFAULT 0,
    is_published   boolean NOT NULL DEFAULT false,
    created_at     timestamptz NOT NULL DEFAULT now(),
    updated_at     timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT service_areas_country_chk CHECK (country IN ('ES','PT')),
    CONSTRAINT service_areas_intro_chk
        CHECK (NOT is_published OR length(coalesce(intro->>'es', '')) >= 200)
);

CREATE UNIQUE INDEX service_areas_slug_es_uq ON service_areas ((slug->>'es'));
CREATE UNIQUE INDEX service_areas_slug_gl_uq ON service_areas ((slug->>'gl'));
CREATE UNIQUE INDEX service_areas_slug_pt_uq ON service_areas ((slug->>'pt'));
CREATE INDEX service_areas_pub_idx ON service_areas (is_published, position);

COMMIT;
