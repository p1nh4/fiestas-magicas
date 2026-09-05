#!/usr/bin/env bash
#
# Instalação inicial. Corre UMA vez, dentro do WSL, na raiz do projeto.
#
#   bash bootstrap.sh
#
# O que faz:
#   1. confirma que tens as ferramentas necessárias
#   2. traz o esqueleto do Laravel 13 SEM apagar nada do que já está aqui
#   3. instala os pacotes
#   4. prepara o .env e a base de dados
#
# É idempotente: podes voltar a correr sem estragar nada.

set -euo pipefail

BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; OFF=$'\033[0m'
step() { printf '\n%s==> %s%s\n' "$BOLD" "$1" "$OFF"; }
ok()   { printf '    %s✓%s %s\n' "$GREEN" "$OFF" "$1"; }
warn() { printf '    %s!%s %s\n' "$YELLOW" "$OFF" "$1"; }
die()  { printf '\n%serro:%s %s\n\n' "$RED" "$OFF" "$1" >&2; exit 1; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

# ---------------------------------------------------------------- 1. requisitos
step "A verificar as ferramentas"

command -v php      >/dev/null || die "não encontro o php. Vê o README (repo Sury para PHP 8.4 no Debian 12)."
command -v composer >/dev/null || die "não encontro o composer."
command -v node     >/dev/null || die "não encontro o node."
command -v npm      >/dev/null || die "não encontro o npm."

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' \
  || die "o Laravel 13 precisa de PHP 8.3 ou superior; tens $PHP_VER."
ok "PHP $PHP_VER"

for ext in pdo_pgsql mbstring xml curl zip gd intl; do
    php -m | grep -qix "$ext" || die "falta a extensão php-$ext. Instala php${PHP_VER}-${ext}."
done
ok "extensões PHP presentes"

# O composer do Windows corre sem openssl e não consegue fazer TLS.
php -r 'exit(extension_loaded("openssl") ? 0 : 1);' \
  || die "o PHP que estás a usar não tem openssl. Estás a usar o do Windows? Usa o do WSL."
ok "composer $(composer --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)"
ok "node $(node -v)"

# ---------------------------------------------------------------- 2. esqueleto
step "Esqueleto do Laravel 13"

if [ -f artisan ]; then
    ok "já existe, salto este passo"
else
    TMP="$(mktemp -d)"
    trap 'rm -rf "$TMP"' EXIT

    composer create-project laravel/laravel "$TMP/skel" "13.*" \
        --no-interaction --no-scripts --quiet
    ok "esqueleto obtido"

    # --ignore-existing: os NOSSOS ficheiros mandam. O esqueleto só preenche
    # o que ainda não existe (public/, config/, bootstrap/, artisan, ...).
    if command -v rsync >/dev/null; then
        rsync -a --ignore-existing "$TMP/skel/" ./
    else
        cp -rn "$TMP/skel/." ./
    fi
    ok "ficheiros do framework copiados (os nossos ficaram intactos)"
fi

# ---------------------------------------------------------------- 3. pacotes
step "Pacotes PHP"

# Sem versões fixadas de propósito: o composer resolve a última compatível
# com o Laravel que está instalado. Só o Filament leva restrição, porque a
# major dele muda a API.
composer require --no-interaction \
    filament/filament:"^5.0" \
    spatie/laravel-translatable \
    spatie/laravel-medialibrary \
    spatie/laravel-permission \
    spatie/laravel-activitylog \
    spatie/laravel-backup \
    spatie/laravel-sitemap \
    stripe/stripe-php

composer require --dev --no-interaction \
    pestphp/pest \
    pestphp/pest-plugin-laravel \
    larastan/larastan \
    laravel/pint

ok "pacotes instalados"

step "Pacotes JavaScript"
npm install --silent
npm install --silent -D tailwindcss @tailwindcss/vite
ok "tailwind instalado"

# ---------------------------------------------------------------- 4. ambiente
step "Ambiente"

if [ ! -f .env ]; then
    cp .env.example .env
    ok ".env criado a partir do .env.example"
else
    warn ".env já existe, não lhe toco"
fi

grep -q '^APP_KEY=base64:' .env || { php artisan key:generate --ansi; ok "APP_KEY gerada"; }

# ---------------------------------------------------------------- 5. base de dados
step "Base de dados"

env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }

DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"
DB_PASS="$(env_value DB_PASSWORD)"
DB_HOST="$(env_value DB_HOST)"

if ! pg_isready -q -h "${DB_HOST:-127.0.0.1}" 2>/dev/null; then
    warn "o Postgres não está a responder. No WSL não há systemd:"
    warn "    sudo service postgresql start"
    warn "Depois volta a correr este script."
    exit 1
fi
ok "Postgres a responder"

# O utilizador e a base de dados podem ainda não existir. Criar precisa de
# sudo, por isso só o tentamos e, se não der, dizemos os comandos exatos.
if ! PGPASSWORD="$DB_PASS" psql -h "${DB_HOST:-127.0.0.1}" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
    warn "não consigo ligar-me a '$DB_NAME' como '$DB_USER'; vou tentar criar"

    if sudo -n true 2>/dev/null || sudo -v 2>/dev/null; then
        sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" \
            | grep -q 1 \
            || sudo -u postgres psql -c "CREATE ROLE \"$DB_USER\" LOGIN PASSWORD '$DB_PASS' CREATEDB;"
        sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" \
            | grep -q 1 \
            || sudo -u postgres createdb -O "$DB_USER" "$DB_NAME"
        ok "base de dados '$DB_NAME' pronta"
    else
        die "sem sudo não consigo criar a base de dados. Corre isto e volta a tentar:
    sudo -u postgres psql -c \"CREATE ROLE \\\"$DB_USER\\\" LOGIN PASSWORD '$DB_PASS' CREATEDB;\"
    sudo -u postgres createdb -O $DB_USER $DB_NAME"
    fi
else
    ok "base de dados '$DB_NAME' acessível"
fi

php artisan migrate --force
ok "migrations aplicadas"

php artisan storage:link 2>/dev/null || true

# ---------------------------------------------------------------- fim
cat <<EOF

$BOLD Pronto.$OFF

 Próximos passos:

   php artisan filament:install --panels     # painel do backoffice
   php artisan make:filament-user            # a tua conta de administrador
   php artisan db:seed                       # catálogo de exemplo

   npm run dev        (num terminal)
   php artisan serve  (noutro)

 Site:       http://localhost:8000
 Backoffice: http://localhost:8000/admin

 Antes de mexeres no modelo de dados, lê database/schema/README.md:
 o esquema é gerado a partir de database/schema/schema.sql, não do
 Laravel. As migrations são geradas — não as edites à mão.

EOF
