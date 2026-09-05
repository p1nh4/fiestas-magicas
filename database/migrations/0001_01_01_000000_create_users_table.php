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
//  A extensao btree_gist e precisa para o indice gist sobre
//  (bigint, tstzrange) das reservas.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE EXTENSION IF NOT EXISTS btree_gist;   -- gist sobre (bigint, tstzrange)

-- =============================================================================
--  1. PESSOAS
-- =============================================================================

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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS sessions CASCADE;
DROP TABLE IF EXISTS password_reset_tokens CASCADE;
DROP TABLE IF EXISTS users CASCADE;
SQL);
    }
};
