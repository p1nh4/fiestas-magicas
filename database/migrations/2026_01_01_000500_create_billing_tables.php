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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS documents CASCADE;
DROP TABLE IF EXISTS document_series CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP FUNCTION IF EXISTS documents_immutability() CASCADE;
SQL);
    }
};
