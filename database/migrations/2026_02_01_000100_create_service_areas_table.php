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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS service_areas CASCADE;
SQL);
    }
};
