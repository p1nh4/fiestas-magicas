#!/usr/bin/env bash
#
# Cópia de segurança: base de dados + ficheiros carregados.
#
#   bash deploy/backup.sh                 cópia diária
#   bash deploy/backup.sh --tag pre-deploy   cópia avulsa com etiqueta
#
# Corre como `deploy`, na raiz do projeto. Lê as credenciais do .env — não
# há passwords escritas aqui.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
[ -f .env ] || { echo "erro: não encontro o .env" >&2; exit 1; }

DEST=${BACKUP_DIR:-/var/backups/fiestas}
KEEP_DAILY=14
KEEP_WEEKLY=8

TAG=""
[ "${1:-}" = "--tag" ] && TAG="-${2:?falta o nome da etiqueta}"

env_get() {
    # Lê uma chave do .env sem o interpretar como shell: um valor com um $
    # ou umas plicas lá dentro não pode virar código.
    sed -n "s/^$1=//p" .env | head -1 | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"
DB_HOST="$(env_get DB_HOST)"
DB_PORT="$(env_get DB_PORT)"
PGPASSWORD="$(env_get DB_PASSWORD)"
export PGPASSWORD

[ -n "$DB_NAME" ] || { echo "erro: DB_DATABASE vazio no .env" >&2; exit 1; }

STAMP="$(date +%Y-%m-%d_%H%M)"
mkdir -p "$DEST/diario" "$DEST/semanal"

DUMP="$DEST/diario/${STAMP}${TAG}.dump"
FILES="$DEST/diario/${STAMP}${TAG}-storage.tar.gz"

# Formato "custom" (-Fc) e não SQL em texto: comprime, e o pg_restore
# consegue restaurar uma tabela só sem ter de reler o ficheiro todo.
pg_dump -Fc -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" \
        -U "$DB_USER" -d "$DB_NAME" -f "$DUMP"

# Só storage/app: é onde estão as fotos e os ficheiros carregados. As
# caches e os logs não são para guardar.
tar czf "$FILES" -C storage app 2>/dev/null || true

chmod 600 "$DUMP" "$FILES"

# Uma cópia de segunda-feira passa a semanal. Assim mantém-se dois meses
# de história sem guardar sessenta ficheiros.
if [ "$(date +%u)" = "1" ] && [ -z "$TAG" ]; then
    cp "$DUMP" "$DEST/semanal/"
    cp "$FILES" "$DEST/semanal/"
fi

# Uma cópia com zero bytes é pior do que não ter cópia nenhuma: dá a
# sensação de estar protegido. Se saiu vazia, isto grita.
if [ ! -s "$DUMP" ]; then
    echo "erro: o dump saiu vazio — $DUMP" >&2
    exit 1
fi

prune() {
    local dir="$1" keep="$2"
    find "$dir" -maxdepth 1 -name '*.dump' -printf '%T@ %p\n' 2>/dev/null \
        | sort -rn | tail -n "+$((keep + 1))" | cut -d' ' -f2- \
        | while read -r old; do
            rm -f "$old" "${old%.dump}-storage.tar.gz"
        done
}

prune "$DEST/diario"  "$KEEP_DAILY"
prune "$DEST/semanal" "$KEEP_WEEKLY"

printf 'cópia feita: %s (%s)\n' "$DUMP" "$(du -h "$DUMP" | cut -f1)"
