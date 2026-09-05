#!/usr/bin/env bash
#
# Prepara um Debian 12/13 limpo para servir a aplicação.
# Corre como root, uma vez. É idempotente.
#
#   bash provision.sh
#
# O que NÃO faz de propósito: não instala a aplicação, não escreve o .env,
# não mete certificados. Isso são passos com decisões tuas pelo meio, e um
# script que os faz sozinho é um script que ninguém lê.

set -euo pipefail

BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; OFF=$'\033[0m'
step() { printf '\n%s==> %s%s\n' "$BOLD" "$1" "$OFF"; }
ok()   { printf '    %s✓%s %s\n' "$GREEN" "$OFF" "$1"; }
warn() { printf '    %s!%s %s\n' "$YELLOW" "$OFF" "$1"; }
die()  { printf '\n%serro:%s %s\n\n' "$RED" "$OFF" "$1" >&2; exit 1; }

APP_DIR=/var/www/fiestas
APP_USER=deploy
DB_NAME=fiestas
DB_USER=fiestas

[ "$(id -u)" -eq 0 ] || die "corre isto como root."
[ -f /etc/debian_version ] || die "isto é para Debian."

export DEBIAN_FRONTEND=noninteractive

# ------------------------------------------------------------------ base
step "Pacotes base"
apt-get update -qq
apt-get install -y -qq \
    ca-certificates curl gnupg lsb-release apt-transport-https \
    git unzip rsync ufw fail2ban unattended-upgrades jq
ok "instalados"

# ------------------------------------------------------------------ PHP
# O Debian 12 traz PHP 8.2 e o projeto precisa de 8.4. O repositório do
# Ondřej Surý é a fonte habitual e é a mesma que se usa no WSL, portanto o
# ambiente de produção e o de desenvolvimento correm a mesma versão.
step "PHP 8.4 (repositório Sury)"
if [ ! -f /etc/apt/sources.list.d/php.list ]; then
    curl -fsSL https://packages.sury.org/php/apt.gpg \
        -o /usr/share/keyrings/sury-php.gpg
    echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
        > /etc/apt/sources.list.d/php.list
    apt-get update -qq
fi

apt-get install -y -qq \
    php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd php8.4-intl php8.4-opcache
ok "PHP $(php -r 'echo PHP_VERSION;')"

# bcmath não é um extra: os totais dos orçamentos somam-se com ele. Sem
# bcmath, o dinheiro passa a somar-se em vírgula flutuante e mais cedo ou
# mais tarde há um total que ninguém consegue explicar ao cliente.
php -r 'extension_loaded("bcmath") || exit(1);' || die "bcmath não carregou."
ok "bcmath presente"

# ------------------------------------------------------------------ composer
step "Composer"
if ! command -v composer >/dev/null; then
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    # Verificar a assinatura não é paranoia: este ficheiro vai correr como
    # root e depois instalar código que serve o site.
    [ "$EXPECTED" = "$ACTUAL" ] || die "a assinatura do instalador do composer não bate certo."
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi
ok "$(composer --version 2>/dev/null | head -1)"

# ------------------------------------------------------------------ node
step "Node LTS"
if ! command -v node >/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null
    apt-get install -y -qq nodejs
fi
ok "node $(node -v)"

# ------------------------------------------------------------------ nginx
step "nginx"
apt-get install -y -qq nginx
ok "instalado"

# ------------------------------------------------------------------ postgres
step "PostgreSQL"
apt-get install -y -qq postgresql postgresql-contrib
systemctl enable --now postgresql
ok "$(sudo -u postgres psql -tAc 'SHOW server_version;' | tr -d ' ')"

DB_PASS=""
if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1; then
    warn "o utilizador '${DB_USER}' já existe — password inalterada"
else
    DB_PASS="$(head -c 32 /dev/urandom | base64 | tr -d '/+=' | head -c 32)"
    sudo -u postgres psql -qc "CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';"
    ok "utilizador criado"
fi

if sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1; then
    warn "a base de dados '${DB_NAME}' já existe"
else
    sudo -u postgres createdb -O "${DB_USER}" "${DB_NAME}"
    ok "base de dados criada"
fi

# As extensões têm de ser criadas por um superutilizador. O btree_gist é o
# que permite o índice GiST das reservas — sem ele, as migrações param.
sudo -u postgres psql -q -d "${DB_NAME}" \
    -c 'CREATE EXTENSION IF NOT EXISTS btree_gist;' \
    -c 'CREATE EXTENSION IF NOT EXISTS pgcrypto;'
ok "extensões btree_gist e pgcrypto"

# O Postgres só escuta em localhost. A aplicação corre na mesma máquina,
# portanto não há razão nenhuma para a base de dados aparecer na rede.
PG_CONF="$(sudo -u postgres psql -tAc 'SHOW config_file;')"
if grep -qE "^\s*listen_addresses\s*=\s*'\*'" "$PG_CONF"; then
    sed -i "s/^\s*listen_addresses\s*=.*/listen_addresses = 'localhost'/" "$PG_CONF"
    systemctl restart postgresql
    ok "listen_addresses fechado a localhost"
fi

# ------------------------------------------------------------------ utilizador
step "Utilizador da aplicação"
if ! id "$APP_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos '' "$APP_USER"
    ok "'${APP_USER}' criado"
else
    warn "'${APP_USER}' já existe"
fi

usermod -aG www-data "$APP_USER"
mkdir -p "$APP_DIR"
chown "${APP_USER}:www-data" "$APP_DIR"
ok "$APP_DIR pronto"

# ------------------------------------------------------------------ php-fpm
step "Pool do PHP-FPM"
if [ -f /tmp/deploy/php/fiestas-pool.conf ]; then
    cp /tmp/deploy/php/fiestas-pool.conf /etc/php/8.4/fpm/pool.d/fiestas.conf
    rm -f /etc/php/8.4/fpm/pool.d/www.conf
    ok "pool instalado"
else
    warn "php/fiestas-pool.conf não encontrado — copia-o à mão"
fi

# Produção não mostra erros ao visitante: um stack trace é um mapa da casa.
PHP_INI=/etc/php/8.4/fpm/php.ini
sed -i 's/^\s*;\?\s*display_errors\s*=.*/display_errors = Off/'                 "$PHP_INI"
sed -i 's/^\s*;\?\s*expose_php\s*=.*/expose_php = Off/'                          "$PHP_INI"
sed -i 's/^\s*;\?\s*upload_max_filesize\s*=.*/upload_max_filesize = 16M/'        "$PHP_INI"
sed -i 's/^\s*;\?\s*post_max_size\s*=.*/post_max_size = 20M/'                    "$PHP_INI"
sed -i 's/^\s*;\?\s*opcache.enable\s*=.*/opcache.enable = 1/'                    "$PHP_INI"
sed -i 's/^\s*;\?\s*opcache.validate_timestamps\s*=.*/opcache.validate_timestamps = 0/' "$PHP_INI"
ok "php.ini endurecido"

# validate_timestamps=0 é o que dá velocidade — o PHP deixa de ir ao disco
# ver se o ficheiro mudou. O preço: depois de cada deploy é OBRIGATÓRIO
# reiniciar o php-fpm, senão continua a servir o código antigo. O
# deploy.sh já o faz.

systemctl enable --now php8.4-fpm
systemctl restart php8.4-fpm

# ------------------------------------------------------------------ firewall
step "Firewall e fail2ban"
ufw --force reset >/dev/null
ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
ufw allow OpenSSH >/dev/null
ufw allow 80/tcp >/dev/null
ufw allow 443/tcp >/dev/null
ufw --force enable >/dev/null
ok "ufw: 22, 80 e 443"

# O 80/443 fica aberto na firewall e é o nginx que filtra por origem —
# assim a lista de intervalos da Cloudflare vive num sítio só, legível com
# um `cat`, em vez de espalhada por regras de firewall.

systemctl enable --now fail2ban
ok "fail2ban a correr"

# ------------------------------------------------------------------ updates
step "Atualizações de segurança automáticas"
cat > /etc/apt/apt.conf.d/20auto-upgrades <<'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
ok "ligadas"

# ------------------------------------------------------------------ fim
step "Feito"
if [ -n "$DB_PASS" ]; then
    printf '\n%sPASSWORD DA BASE DE DADOS%s\n\n    %s\n\n' "$BOLD" "$OFF" "$DB_PASS"
    printf 'Copia-a AGORA para o .env. Não fica guardada em lado nenhum.\n\n'
fi
printf 'Segue no README a partir do passo 2 (certificado).\n\n'
