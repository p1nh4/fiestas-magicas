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
--  O projeto de uma festa: a ideia antes de virar orçamento.
--
--  Nome: chama-se `event_designs` e não `projects` porque `projects` já é o
--  portefólio — os trabalhos publicados no site. São coisas opostas: um é o
--  rascunho privado de uma festa que ainda não aconteceu, o outro é a foto
--  da festa que correu bem. Partilhar o nome era garantir que um dia alguém
--  publicava o rascunho.
--
--  UNIQUE no event_id: um desenho por festa. Sem isto acabavam dois
--  desenhos concorrentes para a mesma festa e ninguém sabia qual valia —
--  que é exatamente o problema que este módulo existe para evitar.
-- ---------------------------------------------------------------------------
CREATE TABLE event_designs (
    id           bigserial PRIMARY KEY,
    uuid         uuid NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    event_id     bigint NOT NULL UNIQUE REFERENCES events(id) ON DELETE CASCADE,
    theme        varchar(120),                       -- "Sirenas", "Fútbol", "Bosque"
    palette      jsonb NOT NULL DEFAULT '[]'::jsonb, -- cores em hexadecimal
    notes        text,                               -- a ideia, por palavras
    inspiration  jsonb NOT NULL DEFAULT '[]'::jsonb, -- links de referência
    photos       jsonb NOT NULL DEFAULT '[]'::jsonb, -- ficheiros carregados
    checklist    jsonb NOT NULL DEFAULT '[]'::jsonb, -- passos da montagem
    created_at   timestamptz NOT NULL DEFAULT now(),
    updated_at   timestamptz NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------------
--  O material que o desenho prevê.
--
--  NÃO é uma reserva. É uma intenção: "para esta festa penso levar isto".
--  A reserva só nasce quando o orçamento é aceite — é lá que o trigger do
--  stock decide. Confundir as duas coisas seria prender material por causa
--  de uma ideia que ainda pode mudar.
-- ---------------------------------------------------------------------------
CREATE TABLE event_design_items (
    id           bigserial PRIMARY KEY,
    design_id    bigint NOT NULL REFERENCES event_designs(id) ON DELETE CASCADE,
    item_id      bigint NOT NULL REFERENCES items(id) ON DELETE RESTRICT,
    quantity     int NOT NULL DEFAULT 1 CHECK (quantity > 0),
    notes        varchar(200),
    position     int NOT NULL DEFAULT 0,
    created_at   timestamptz NOT NULL DEFAULT now(),
    updated_at   timestamptz NOT NULL DEFAULT now(),
    -- A mesma peça duas vezes no mesmo desenho é sempre um engano: o que se
    -- queria era mudar a quantidade.
    CONSTRAINT event_design_items_uq UNIQUE (design_id, item_id)
);

CREATE INDEX event_design_items_design_idx ON event_design_items (design_id, position);
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS event_design_items CASCADE;
DROP TABLE IF EXISTS event_designs CASCADE;
SQL);
    }
};
