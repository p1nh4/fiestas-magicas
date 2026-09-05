<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Gerada a partir de database/schema/schema.sql
|--------------------------------------------------------------------------
| NAO editar a mao. O esquema vive em database/schema/schema.sql, que e
| corrido e testado contra um Postgres real (database/schema/tests.sql).
| Para alterar o modelo: mexer no schema.sql, correr os testes, e regerar.
|
| E SQL em bruto de proposito: jsonb com indices sobre expressoes, tstzrange,
| indices gist e triggers plpgsql nao se exprimem no Schema builder do Laravel.
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS quote_lines CASCADE;
DROP TABLE IF EXISTS quotes CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS leads CASCADE;
SQL);
    }
};
