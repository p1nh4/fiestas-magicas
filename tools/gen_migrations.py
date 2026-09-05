"""Gera as migrations do Laravel a partir de schema.sql.

Porque nao a mao: schema.sql e a fonte de verdade e esta testado. Se as
migrations forem escritas a mao, mais cedo ou mais tarde divergem do que
foi testado. Assim, gerar e reproduzivel e a divergencia e impossivel.
"""
import re, os, pathlib

SRC = 'schema.sql'
OUT = pathlib.Path('build/database/migrations')

# nome da fatia -> (ficheiro da migration, classe descritiva)
PLAN = [
    (['core', 'users'], '0001_01_01_000000_create_users_table.php'),
    (['clients'],       '2026_01_01_000100_create_clients_table.php'),
    (['catalog'],       '2026_01_01_000200_create_catalog_tables.php'),
    (['sales'],         '2026_01_01_000300_create_sales_tables.php'),
    (['reservations'],  '2026_01_01_000400_create_reservations_table.php'),
    (['billing'],       '2026_01_01_000500_create_billing_tables.php'),
    (['content'],       '2026_01_01_000600_create_content_tables.php'),
]


def load_slices(path):
    raw = open(path, encoding='utf-8').read()
    raw = re.sub(r'^\s*(BEGIN|COMMIT)\s*;\s*$', '', raw, flags=re.M)
    parts = re.split(r'^-- @@SLICE (\w+)\s*$', raw, flags=re.M)
    # parts[0] = cabecalho antes do primeiro marcador
    slices = {}
    for i in range(1, len(parts), 2):
        slices[parts[i]] = parts[i + 1].strip('\n')
    return slices


def objects_of(sql):
    """Tabelas e funcoes criadas, pela ordem em que aparecem."""
    tables = re.findall(r'CREATE TABLE (\w+)', sql)
    funcs = re.findall(r'CREATE OR REPLACE FUNCTION (\w+)\(', sql)
    return tables, funcs


def php_migration(sql, tables, funcs, has_extension):
    drops = []
    for t in reversed(tables):
        drops.append(f'DROP TABLE IF EXISTS {t} CASCADE;')
    for f in funcs:
        drops.append(f'DROP FUNCTION IF EXISTS {f}() CASCADE;')
    down_sql = '\n'.join(drops)

    note = ''
    if has_extension:
        note = ("//  A extensao btree_gist e precisa para o indice gist sobre\n"
                "//  (bigint, tstzrange) das reservas.\n")

    return f'''<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;

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
{note}return new class extends Migration
{{
    public function up(): void
    {{
        DB::unprepared(<<<'SQL'
{sql}
SQL);
    }}

    public function down(): void
    {{
        DB::unprepared(<<<'SQL'
{down_sql}
SQL);
    }}
}};
'''


def main():
    slices = load_slices(SRC)
    unused = set(slices) - {n for names, _ in PLAN for n in names}
    if unused:
        raise SystemExit(f'fatias sem migration: {unused}')

    OUT.mkdir(parents=True, exist_ok=True)
    for old in OUT.glob('*.php'):
        old.unlink()

    total_tables = 0
    for names, filename in PLAN:
        sql = '\n\n'.join(slices[n] for n in names).strip()
        tables, funcs = objects_of(sql)
        total_tables += len(tables)
        has_ext = 'CREATE EXTENSION' in sql
        (OUT / filename).write_text(
            php_migration(sql, tables, funcs, has_ext), encoding='utf-8')
        print(f'  {filename:52s} {len(tables):2d} tabelas, {len(funcs)} funcoes')

    print(f'\n{len(PLAN)} migrations, {total_tables} tabelas no total')


if __name__ == '__main__':
    main()
