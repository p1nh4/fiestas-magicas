# Pôr o site no ar numa máquina tua

Variante do `README.md` para quando não há IP público: o WSL2 do portátil,
um mini-PC em casa, um Raspberry Pi. Lê o `README.md` primeiro — este
ficheiro só descreve o que muda.

**Isto não foi corrido em lado nenhum.** Confere cada passo.

---

## A decisão que molda tudo o resto

No `README.md` a máquina tem IP público e fica *atrás* da Cloudflare. Aqui
não tem, e a maior parte das fibras residenciais portuguesas está atrás de
CGNAT: não há IPv4 teu, e abrir portas no router não resolve nada porque a
porta não é tua.

A peça que resolve isto é um **túnel nomeado** da Cloudflare. O
`cloudflared` corre na tua máquina e abre uma ligação **de dentro para
fora**. O tráfego entra pela borda da Cloudflare e desce por essa ligação.

O que isso implica, e é quase tudo bom:

- **Nenhuma porta aberta.** Nem no router, nem na máquina. É mais fechado
  do que a variante com IP público, não menos.
- **Sem certificado de origem, sem porta 443, sem lista de intervalos da
  Cloudflare, sem `deny all`.** O nginx só escuta em `127.0.0.1:8080` e
  quem lhe fala é o `cloudflared`, aqui ao lado.
- **O IP do visitante continua a ser o problema a resolver.** Muda de sítio:
  a ligação chega sempre de `127.0.0.1`, portanto é dele — e só dele — que
  se aceita o `CF-Connecting-IP`. Se isto ficar mal, **todos** os visitantes
  passam a ser 127.0.0.1: o `throttle:6,1` do formulário trava toda a gente
  à sexta submissão do dia e o `Lead::hashIp()` grava sempre o mesmo hash.
  Está tratado no `nginx/site-tunnel.conf`.

---

## Passo 0 — systemd no WSL2 (salta se for máquina Linux a sério)

Sem systemd não há timers, e sem timers não há agendador — que é a peça
cuja falha é silenciosa.

Dentro do WSL:

```bash
sudo tee /etc/wsl.conf >/dev/null <<'EOF'
[boot]
systemd=true
EOF
```

Depois, no PowerShell do Windows: `wsl --shutdown`. Volta a abrir e
confirma com `systemctl is-system-running` (`running` ou `degraded` servem;
`offline` não).

## Passo 1 — preparar a máquina

```bash
sudo bash deploy/provision.sh
```

Duas ressalvas no WSL: o `ufw` não serve para nada (quem filtra é o
Windows) e o `unattended-upgrades` também não, porque a máquina não está
sempre ligada. Não fazem mal — deixa-os.

Guarda a password da base de dados que ele imprime no fim.

## Passo 2 — a aplicação, em sítio próprio

**Não sirvas o teu `~/fiestasmagicasgalicia`.** É onde tens `npm run dev`,
edições a meio e `php artisan test`. Um site público a servir a pasta onde
programas é um site que muda quando gravas um ficheiro por engano.

Segue o **passo 4 do `README.md`** tal como está: clone para
`/var/www/fiestas`, `.env` próprio a partir do `.env.production.example`,
`composer install --no-dev`, `npm ci && npm run build`, `migrate`, `seed`,
`optimize`, `make:filament-user`. Depois o passo 5, das permissões.

No `.env`, além do que o exemplo já diz:

```env
APP_URL=https://fiestasmagicasengalicia.com
MONITOR_REQUIRE_SCHEDULER=true
```

## Passo 3 — nginx

```bash
sudo cp deploy/nginx/site-tunnel.conf /etc/nginx/sites-available/fiestas
sudo ln -sf /etc/nginx/sites-available/fiestas /etc/nginx/sites-enabled/fiestas
sudo rm -f /etc/nginx/sites-enabled/default
sudo sed -i 's/DOMINIO\.TLD/fiestasmagicasengalicia.com/g' /etc/nginx/sites-available/fiestas
sudo nginx -t && sudo systemctl reload nginx
curl -sI http://127.0.0.1:8080/up | head -1     # 200 antes de haver túnel
```

**Não** corras o `update-cloudflare-ips.sh` nem ligues o
`cloudflare-ips.timer`: aqui não há lista de intervalos a manter.

## Passo 4 — o túnel

```bash
# repositório da Cloudflare
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg \
  | sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null
echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" \
  | sudo tee /etc/apt/sources.list.d/cloudflared.list
sudo apt-get update && sudo apt-get install -y cloudflared

cloudflared tunnel login      # imprime uma URL; abre-a no browser do Windows
cloudflared tunnel create fiestas
```

O `create` diz o **UUID** e onde ficou o ficheiro de credenciais
(`~/.cloudflared/<UUID>.json`). Esse ficheiro é a chave do túnel: quem o
tiver serve tráfego neste domínio.

```bash
sudo mkdir -p /etc/cloudflared
sudo cp ~/.cloudflared/<UUID>.json /etc/cloudflared/
sudo cp deploy/cloudflared/config.example.yml /etc/cloudflared/config.yml
sudo nano /etc/cloudflared/config.yml         # UUID e domínio

sudo useradd -r -s /usr/sbin/nologin cloudflared 2>/dev/null || true
sudo chown -R cloudflared:cloudflared /etc/cloudflared
sudo chmod 600 /etc/cloudflared/*.json

sudo cp deploy/systemd/cloudflared.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cloudflared
journalctl -u cloudflared -n 30 --no-pager
```

## Passo 5 — DNS

O domínio tem de estar **na Cloudflare** (nameservers mudados no
registrar). Depois, uma linha:

```bash
cloudflared tunnel route dns fiestas fiestasmagicasengalicia.com
cloudflared tunnel route dns fiestas www.fiestasmagicasengalicia.com
```

Isto cria os CNAME já com a nuvem laranja. Não crias registo `A` nenhum —
não há IP para lá pôr, e é essa a graça.

No painel: **Always Use HTTPS** ligado. O modo de encriptação SSL/TLS deixa
de ser a peça que protege a origem — quem o faz é o próprio túnel, que sai
desta máquina já cifrado.

## Passo 6 — serviços de fundo

Passo 6 do `README.md`, **menos** o `cloudflare-ips.timer`:

```bash
sudo cp deploy/systemd/fiestas-*.service deploy/systemd/fiestas-*.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now fiestas-queue.service
sudo systemctl enable --now fiestas-scheduler.timer
sudo systemctl enable --now fiestas-backup.timer
```

## Passo 7 — confirmar

```bash
curl -sI https://fiestasmagicasengalicia.com | head -1        # 200
curl -s  https://fiestasmagicasengalicia.com/up | head -1     # sem 503
systemctl list-timers fiestas-scheduler.timer --no-pager
```

E o que interessa mesmo, porque é o que se estraga em silêncio: **os IPs**.
Submete o formulário do site a partir do telemóvel com os dados móveis
ligados (rede diferente da de casa), e depois do PC. Na base de dados:

```sql
SELECT ip_hash, created_at FROM leads ORDER BY id DESC LIMIT 5;
```

Os dois têm de dar hashes **diferentes**. Se derem o mesmo, o
`set_real_ip_from` do passo 3 não está a funcionar e o formulário está sem
proteção nenhuma contra spam por origem.

---

## O que isto não é

O teu PC não é um servidor, e nenhuma configuração o torna num.

- Adormece com o Windows, reinicia para atualizações, e vai abaixo com a
  luz. Sempre que isso acontece o site fica em baixo — e quem estava a
  preencher o formulário perde o que escreveu.
- O `/up` fica a responder 503 (ou nada) e, se já tiveres o serviço externo
  a bater à porta, recebes o alarme. É melhor saber do que não saber, mas
  continua a ser um lead perdido.

Serve para ver isto no ar, para mostrar à Sol, e para exercitar o runbook —
que é muito mais do que um túnel improvisado dá. Não serve para receber
pedidos de clientes a sério.

## Passar para uma máquina a sério

Um mini-PC ou um Raspberry Pi 5 usado, sempre ligado, chega e sobra para
Laravel e Postgres com este tráfego. A mudança é os mesmos passos noutra
máquina, mais:

```bash
cloudflared tunnel route dns fiestas fiestasmagicasengalicia.com   # já feito
```

O túnel é o mesmo, o DNS não muda. Copias `/etc/cloudflared/`, restauras a
cópia de segurança mais recente com o `restore.sh`, e desligas o antigo.
Do lado da Cloudflare, ninguém deu por nada.

Se um dia houver máquina com IP público, é o `README.md` outra vez, e o
`site.conf` em vez do `site-tunnel.conf`.
