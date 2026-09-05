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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS clients CASCADE;
SQL);
    }
};
