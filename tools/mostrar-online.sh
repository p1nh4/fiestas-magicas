#!/usr/bin/env bash
#
# Põe o site a correr num endereço HTTPS público, a partir deste portátil.
#
#   bash tools/mostrar-online.sh
#
# Para quê: mostrar à Sol sem ter servidor. Ela abre o link no telemóvel,
# de onde estiver, e vê exatamente o que está aqui.
#
# Como: um túnel da Cloudflare. Ela liga-se à Cloudflare e a Cloudflare
# liga-se a este portátil — não se abre nenhuma porta no router, não é
# preciso IP fixo, e o TLS é dela.
#
# Isto é para MOSTRAR, não para pôr no ar. O endereço morre quando fechares
# o script, e enquanto correr o teu portátil é o servidor: se o fechares a
# meio de uma demonstração, o site desaparece. Para o site a sério existe o
# deploy/.
#
# Três armadilhas que este script resolve e que à mão se esquecem:
#
#   1. O APP_URL tem de passar a ser o endereço do túnel. Sem isso, o
#      Laravel gera links para localhost e no telemóvel dela não abrem.
#
#   2. O `npm run dev` serve o CSS de localhost:5173 — que existe aqui e
#      não existe no telemóvel dela. Tem de ser `npm run build`.
#
#   3. O .env tem de voltar ao que estava. Sem isso ficas com um APP_URL
#      morto e os testes começam a falhar por razões que ninguém liga a isto.

set -euo pipefail

BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; OFF=$'\033[0m'
step() { printf '\n%s==> %s%s\n' "$BOLD" "$1" "$OFF"; }
ok()   { printf '    %s✓%s %s\n' "$GREEN" "$OFF" "$1"; }
warn() { printf '    %s!%s %s\n' "$YELLOW" "$OFF" "$1"; }
die()  { printf '\n%serro:%s %s\n\n' "$RED" "$OFF" "$1" >&2; exit 1; }

cd "$(dirname "${BASH_SOURCE[0]}")/.."
[ -f artisan ] || die "não estou na raiz do projeto."
[ -f .env ] || die "não encontro o .env."

PORT="${PORT:-8080}"
LOG_DIR="storage/logs"
TUNNEL_LOG="$LOG_DIR/tunnel.log"
SERVE_LOG="$LOG_DIR/mostrar-online-serve.log"
mkdir -p "$LOG_DIR"

# Quantas portas se experimentam a partir da $PORT antes de desistir. Ter o
# `php artisan serve` aberto noutro terminal e o caso normal, nao um erro:
# o script procura outra porta em vez de mandar fechar o que estava a
# funcionar.
PORT_TENTATIVAS="${PORT_TENTATIVAS:-20}"

# Nada disto esta escrito a meio do script mais abaixo: uma versao nova do
# cloudflared, outro dominio de tuneis, outra arquitetura — muda-se aqui,
# ou passa-se por ambiente, e o resto do script nao sabe a diferenca.
#
#   CF_ARCH=arm64 bash tools/mostrar-online.sh
#
CF_ARCH="${CF_ARCH:-amd64}"
CF_RELEASE="${CF_RELEASE:-latest/download}"
CF_BASE="${CF_BASE:-https://github.com/cloudflare/cloudflared/releases}"
CF_DEB_URL="${CF_DEB_URL:-${CF_BASE}/${CF_RELEASE}/cloudflared-linux-${CF_ARCH}.deb}"

# O dominio dos tuneis efemeros. Esta aqui porque e ele que define o que o
# grep mais abaixo procura no log — as duas coisas tem de andar juntas, e
# separa-las era a maneira certa de um dia mudar uma e esquecer a outra.
TUNNEL_HOST="${TUNNEL_HOST:-trycloudflare.com}"
TUNNEL_URL_RE="https://[a-z0-9-]+\.${TUNNEL_HOST//./\\.}"

# Quanto tempo se espera pelo endereco, em segundos.
TUNNEL_TIMEOUT="${TUNNEL_TIMEOUT:-30}"

# ------------------------------------------------------------ cloudflared
if ! command -v cloudflared >/dev/null; then
    cat <<AJUDA

Falta o cloudflared. Instala-o uma vez, no WSL:

    curl -fsSL ${CF_DEB_URL} -o /tmp/cloudflared.deb
    sudo dpkg -i /tmp/cloudflared.deb

Não precisa de conta nem de configuração para isto.

AJUDA
    exit 1
fi

# ------------------------------------------------------------ guardar o .env
ORIGINAL_APP_URL="$(sed -n 's/^APP_URL=//p' .env | head -1)"
[ -n "$ORIGINAL_APP_URL" ] || die "o .env não tem linha APP_URL=. Acrescenta-a antes de correr isto."
SERVE_PID=""
TUNNEL_PID=""

ARRUMADO=0

restaurar() {
    # O Ctrl+C dispara o INT e a seguir o EXIT. Sem esta guarda, o sed corria
    # duas vezes — inofensivo hoje, mas e o tipo de coisa que morde quando
    # alguem lhe acrescentar um passo que nao seja idempotente.
    [ "$ARRUMADO" -eq 1 ] && return 0
    ARRUMADO=1

    printf '\n'
    step "A arrumar"

    [ -n "$TUNNEL_PID" ] && kill "$TUNNEL_PID" 2>/dev/null || true
    [ -n "$SERVE_PID" ] && kill "$SERVE_PID" 2>/dev/null || true

    if [ -n "$ORIGINAL_APP_URL" ]; then
        sed -i "s|^APP_URL=.*|APP_URL=${ORIGINAL_APP_URL}|" .env
        ok "APP_URL de volta a ${ORIGINAL_APP_URL}"
    fi

    php artisan config:clear >/dev/null 2>&1 || true
    ok "caches limpas"
    printf '\n'
}
trap restaurar EXIT INT TERM

# ------------------------------------------------------------ compilar
step "A compilar o CSS e o JS"
# `build` e nao `dev`: o servidor do Vite so responde neste portatil, e o
# telemovel dela nao lhe chega. Com o build, os ficheiros ficam em
# public/build e sao servidos pelo proprio Laravel.
# Mesma regra do servidor: a saida vai para um ficheiro e mostra-se se
# falhar. Um "falhou, ve tu porque" nao ajuda ninguem as onze da noite.
BUILD_LOG="$LOG_DIR/mostrar-online-build.log"
if ! npm run build > "$BUILD_LOG" 2>&1; then
    printf '\n'
    sed 's/^/    /' "$BUILD_LOG" | tail -15
    die "o npm run build falhou. O que ele disse está aí em cima (e em ${BUILD_LOG})."
fi
ok "compilado"

# ------------------------------------------------------------ servidor
#
# Ha alguem a ouvir nesta porta?
#
# `/dev/tcp` e do proprio bash: tenta ligar-se, e se conseguir e porque
# esta ocupada. Nao precisa de `ss`, nem de `lsof`, nem de `netstat` — que
# ora estao instalados ora nao, e no WSL costumam nao estar.
ocupada() {
    (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null
}

step "A arrancar o Laravel"

PORTA_INICIAL="$PORT"
while ocupada "$PORT"; do
    PORT=$((PORT + 1))
    if [ "$PORT" -ge $((PORTA_INICIAL + PORT_TENTATIVAS)) ]; then
        die "da ${PORTA_INICIAL} à ${PORT} está tudo ocupado. Fecha alguma coisa, ou escolhe: PORT=9000 bash tools/mostrar-online.sh"
    fi
done

[ "$PORT" = "$PORTA_INICIAL" ] || warn "a ${PORTA_INICIAL} estava ocupada — vai pela ${PORT}"

# A saida vai para um ficheiro, e nao para /dev/null: quando isto falha, o
# motivo esta nessas tres linhas, e adivinha-lo em voz alta ("a porta estara
# ocupada?") e pior do que nao dizer nada.
: > "$SERVE_LOG"
php artisan serve --host=127.0.0.1 --port="$PORT" > "$SERVE_LOG" 2>&1 &
SERVE_PID=$!
sleep 2

if ! kill -0 "$SERVE_PID" 2>/dev/null; then
    printf '\n'
    sed 's/^/    /' "$SERVE_LOG" | tail -12
    die "o servidor não arrancou. O que ele disse está aí em cima (e em ${SERVE_LOG})."
fi

ok "a correr na porta ${PORT} (pid ${SERVE_PID})"

# ------------------------------------------------------------ túnel
step "A abrir o túnel"
: > "$TUNNEL_LOG"
cloudflared tunnel --url "http://127.0.0.1:${PORT}" --no-autoupdate > "$TUNNEL_LOG" 2>&1 &
TUNNEL_PID=$!

URL=""
for _ in $(seq 1 "$TUNNEL_TIMEOUT"); do
    URL="$(grep -oE "$TUNNEL_URL_RE" "$TUNNEL_LOG" | head -1 || true)"
    [ -n "$URL" ] && break
    kill -0 "$TUNNEL_PID" 2>/dev/null || die "o cloudflared morreu. Vê ${TUNNEL_LOG}."
    sleep 1
done

[ -n "$URL" ] || die "o túnel não deu endereço em ${TUNNEL_TIMEOUT} s. Vê ${TUNNEL_LOG}."
ok "$URL"

# ------------------------------------------------------------ APP_URL
step "A apontar o Laravel para esse endereço"
sed -i "s|^APP_URL=.*|APP_URL=${URL}|" .env
php artisan config:clear >/dev/null 2>&1 || true
ok "APP_URL = ${URL}"

# ------------------------------------------------------------ pronto
cat <<FIM

${BOLD}Pronto. Manda-lhe este link:${OFF}

    ${URL}/es          o site
    ${URL}/admin       o backoffice

${YELLOW}Enquanto isto correr, o servidor é este portátil.${OFF} Se o fechares ou
adormecer, o link deixa de responder. O endereço é novo de cada vez.

Ctrl+C para fechar e pôr o .env como estava.

FIM

# Fica à espera do túnel. O trap trata do resto quando carregares em Ctrl+C.
wait "$TUNNEL_PID"
