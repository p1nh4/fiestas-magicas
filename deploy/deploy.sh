#!/usr/bin/env bash
#
# Atualiza a aplicação em produção. Corre como o utilizador `deploy`, na
# raiz do projeto:
#
#   bash deploy/deploy.sh
#
# Não é um deploy sem interrupção — é um servidor só, e fingir o contrário
# acrescentava complexidade a troco de nada. São uns segundos de página de
# manutenção, e volta.

set -euo pipefail

BOLD=$'\033[1m'; GREEN=$'\033[32m'; RED=$'\033[31m'; OFF=$'\033[0m'
step() { printf '\n%s==> %s%s\n' "$BOLD" "$1" "$OFF"; }
ok()   { printf '    %s✓%s %s\n' "$GREEN" "$OFF" "$1"; }
die()  { printf '\n%serro:%s %s\n\n' "$RED" "$OFF" "$1" >&2; exit 1; }

cd "$(dirname "${BASH_SOURCE[0]}")/.."
[ -f artisan ] || die "não estou na raiz do projeto."

grep -q '^APP_ENV=production' .env || die "o .env não diz APP_ENV=production. Pára aqui."

# Uma cópia antes de mexer. As migrações são o passo irreversível deste
# script; se correr mal, é daqui que se volta atrás.
step "Cópia de segurança antes de mexer"
bash deploy/backup.sh --tag pre-deploy || die "a cópia falhou — não avanço."
ok "guardada"

step "Código"
git pull --ff-only
ok "$(git log -1 --pretty='%h %s')"

step "Manutenção"
php artisan down --retry=15 || true

# A partir daqui, aconteça o que acontecer, o site volta a abrir.
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

step "Dependências"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent
npm run build
ok "instaladas e compiladas"

step "Base de dados"
php artisan migrate --force
ok "migrada"

step "Caches"
php artisan optimize
ok "config, rotas e views em cache"

step "Serviços"
sudo systemctl restart php8.4-fpm
# O opcache está com validate_timestamps=0: sem este restart, o PHP
# continuava a servir o código antigo sem dar um único sinal.
php artisan queue:restart
ok "php-fpm reiniciado, fila avisada"

php artisan up
trap - EXIT
ok "site no ar"

step "Confirmação"
# Nota: um `curl` a 127.0.0.1 daqui dá 403, e isso está CERTO — o nginx só
# aceita ligações vindas da Cloudflare. Por isso o que se verifica aqui é
# que a aplicação arranca; o resto confirma-se no browser.
php artisan about --only=environment >/dev/null || die "a aplicação não arranca. Vê storage/logs/laravel.log."
ok "a aplicação arranca com a configuração nova"

printf '\n%sPronto.%s Abre o site no browser antes de te ires embora.\n\n' "$BOLD" "$OFF"
