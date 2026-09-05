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
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
DROP TABLE IF EXISTS reservations CASCADE;
DROP FUNCTION IF EXISTS reservations_check_capacity() CASCADE;
DROP FUNCTION IF EXISTS items_recheck_capacity() CASCADE;
SQL);
    }
};
