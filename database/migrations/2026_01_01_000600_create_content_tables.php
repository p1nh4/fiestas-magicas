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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS newsletter_subscribers CASCADE;
DROP TABLE IF EXISTS redirects CASCADE;
DROP TABLE IF EXISTS testimonials CASCADE;
DROP TABLE IF EXISTS faqs CASCADE;
DROP TABLE IF EXISTS pages CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
SQL);
    }
};
