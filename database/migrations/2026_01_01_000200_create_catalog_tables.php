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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS items CASCADE;
DROP TABLE IF EXISTS services CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
SQL);
    }
};
