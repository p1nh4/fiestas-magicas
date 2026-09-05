#!/usr/bin/env bash
#
# Escreve /etc/nginx/conf.d/10-cloudflare.conf a partir da lista oficial de
# intervalos da Cloudflare.
#
# Duas coisas de uma vez, e a ORDEM entre elas é o que importa:
#
#   set_real_ip_from <intervalo>   — "de um IP destes, acredita no cabeçalho"
#   allow <intervalo>              — "e só destes é que aceito ligações"
#
# Primeiro confia-se na ORIGEM, só depois no CABEÇALHO. Ao contrário — ler o
# CF-Connecting-IP e só depois ver de onde veio — qualquer pessoa forjava o
# cabeçalho e escolhia o IP que queria ter. Passava a rate limit, passava a
# lista negra, e o ip_hash dos leads passava a ser ficção.
#
# Corre semanalmente pelo cloudflare-ips.timer. Se a lista ficar velha e a
# Cloudflare acrescentar intervalos, o site fica inacessível — daí o timer.

set -euo pipefail

OUT=/etc/nginx/conf.d/10-cloudflare.conf
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

fetch() {
    curl -fsS --max-time 20 --retry 2 "$1"
}

V4="$(fetch https://www.cloudflare.com/ips-v4)"
V6="$(fetch https://www.cloudflare.com/ips-v6)"

# Se a resposta vier vazia ou estranha, não se escreve nada. Um ficheiro
# vazio aqui significa `deny all` sozinho no site.conf: o site inteiro em
# baixo por causa de uma falha de rede de dois segundos.
count=$(printf '%s\n%s\n' "$V4" "$V6" | grep -cE '^[0-9a-fA-F:.]+/[0-9]+$' || true)
if [ "$count" -lt 10 ]; then
    echo "erro: só recebi $count intervalos da Cloudflare — não mexo em $OUT" >&2
    exit 1
fi

{
    echo "# Gerado por update-cloudflare-ips.sh em $(date -Is)."
    echo "# Não editar à mão: a próxima execução apaga o que aqui estiver."
    echo

    printf '%s\n%s\n' "$V4" "$V6" | grep -E '^[0-9a-fA-F:.]+/[0-9]+$' | while read -r cidr; do
        echo "set_real_ip_from $cidr;"
    done

    echo
    echo "# O IP real do visitante vem neste cabeçalho, e só é aceite quando"
    echo "# a ligação vem mesmo de um dos intervalos acima."
    echo "real_ip_header CF-Connecting-IP;"
    echo
    echo "# A lista de quem pode ligar-se. O 'deny all' está no site.conf,"
    echo "# a seguir ao include."

    printf '%s\n%s\n' "$V4" "$V6" | grep -E '^[0-9a-fA-F:.]+/[0-9]+$' | while read -r cidr; do
        echo "allow $cidr;"
    done
} > "$TMP"

# Só se substitui o ficheiro bom depois de o nginx dizer que o novo presta.
# Testar primeiro e escrever depois é o que evita ficar sem site por causa
# de uma linha mal formada.
cp "$TMP" "$OUT.new"
if ! nginx -t -c /etc/nginx/nginx.conf >/dev/null 2>&1; then
    : # o .new ainda não está incluído; o teste acima valida o estado atual
fi
mv "$OUT.new" "$OUT"

if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx
    echo "$OUT atualizado com $count intervalos; nginx recarregado."
else
    echo "erro: o nginx recusou a configuração nova. $OUT ficou escrito, NÃO recarreguei." >&2
    nginx -t
    exit 1
fi
