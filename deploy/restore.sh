#!/usr/bin/env bash
#
# Restaurar uma cópia de segurança.
#
#   bash deploy/restore.sh FICHEIRO.dump --dry-run   ensaio, não toca na produção
#   bash deploy/restore.sh FICHEIRO.dump             a sério, com confirmação
#
# O ensaio é o que interessa fazer todos os meses. Uma cópia que nunca foi
# restaurada não é uma cópia de segurança — é um ficheiro com esperança lá
# dentro. Descobrir que não presta no dia em que faz falta é tarde.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

DUMP="${1:?uso: restore.sh FICHEIRO.dump [--dry-run]}"
MODE="${2:-}"
[ -f "$DUMP" ] || { echo "erro: não encontro $DUMP" >&2; exit 1; }

env_get() {
    sed -n "s/^$1=//p" .env | head -1 | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"
DB_HOST="$(env_get DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)"; DB_PORT="${DB_PORT:-5432}"
PGPASSWORD="$(env_get DB_PASSWORD)"
export PGPASSWORD

if [ "$MODE" = "--dry-run" ]; then
    SCRATCH="${DB_NAME}_ensaio_$$"
    echo "Ensaio: a restaurar para '$SCRATCH'. A produção não é tocada."

    createdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$SCRATCH"
    trap 'dropdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$SCRATCH" >/dev/null 2>&1 || true' EXIT

    pg_restore -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$SCRATCH" --no-owner "$DUMP"

    echo
    echo "Linhas restauradas:"
    for t in clients events quotes quote_lines items services leads reservations payments; do
        n=$(psql -tA -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$SCRATCH" \
            -c "SELECT count(*) FROM $t" 2>/dev/null || echo "—")
        printf '  %-14s %s\n' "$t" "$n"
    done
    echo
    echo "Se estes números fazem sentido, a cópia presta. A base de dados de ensaio vai ser apagada."
    exit 0
fi

cat <<AVISO

  ATENÇÃO
  Isto APAGA a base de dados '$DB_NAME' e põe lá o conteúdo de:
      $DUMP

  Tudo o que tenha acontecido desde essa cópia perde-se.

AVISO
read -r -p "Escreve o nome da base de dados para confirmar: " confirm
[ "$confirm" = "$DB_NAME" ] || { echo "cancelado."; exit 1; }

php artisan down --retry=60 || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

dropdb   -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME"
createdb -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" "$DB_NAME"
psql -q -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
     -c 'CREATE EXTENSION IF NOT EXISTS btree_gist;' \
     -c 'CREATE EXTENSION IF NOT EXISTS pgcrypto;'
pg_restore -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" --no-owner "$DUMP"

php artisan up
trap - EXIT
echo "restaurado."
