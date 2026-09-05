# Pôr o site no ar

Um servidor Debian atrás da Cloudflare. Sem serviços pagos, sem Docker, sem
nada que precise de subscrição. Tudo o que está aqui é legível com `cat` —
essa é a ideia: se não se consegue auditar, não se consegue confiar.

**Nada disto foi testado num servidor a sério.** Foi escrito e verificado
estaticamente (`shellcheck`, `nginx -t` fá-lo no destino). Corre pela ordem
abaixo e confere cada passo — não corras tudo de seguida às cegas.

---

## A decisão que molda tudo o resto

O domínio está na Cloudflare, portanto o servidor fica **atrás** dela. Três
consequências, e a terceira é a que morde:

1. **TLS sem renovações.** Um *Origin Certificate* da Cloudflare vale 15
   anos. Não há certbot, não há cron de renovação a falhar às 3 da manhã.
   Em troca, o modo de encriptação tem de ficar em **Full (strict)**.

2. **O servidor pode fechar-se ao mundo.** Só aceita ligações vindas dos
   intervalos de IP da Cloudflare. Quem descobrir o IP da máquina e tentar
   ligar-se diretamente leva com um 403 antes de chegar ao PHP.

3. **O IP do visitante deixa de ser o IP da ligação.** É aqui que se
   estraga tudo se não se pensar nisto:

   - o `throttle:6,1` do formulário passaria a contar **todos** os
     visitantes como se fossem um só — o primeiro robot bloqueava o
     formulário para toda a gente;
   - o `Lead::hashIp()` guardaria sempre o mesmo hash, e a deteção de spam
     por origem deixava de significar nada.

   A correção está em `nginx/10-cloudflare.conf`: o nginx reescreve o IP da
   ligação a partir do cabeçalho `CF-Connecting-IP`, mas **só** quando a
   ligação vem mesmo de um IP da Cloudflare. A partir daí o PHP vê o
   `REMOTE_ADDR` verdadeiro e não é preciso mexer em `trustProxies` no
   Laravel.

   Repara na ordem: primeiro confia-se na origem, só depois no cabeçalho.
   Ao contrário — confiar no cabeçalho e depois ver de onde veio — qualquer
   pessoa forjava o `CF-Connecting-IP` e escolhia que IP queria ter.

---

## Antes de começar

Precisas de:

- um Debian 12 ou 13 com acesso root e um IP fixo
- o domínio já na Cloudflare (está)
- um *Origin Certificate* gerado no painel da Cloudflare
  (SSL/TLS → Origin Server → Create Certificate). Guarda os dois blocos:
  o certificado e a chave privada. A chave só aparece uma vez.

---

## Passo 1 — preparar a máquina

Como root, no servidor:

```bash
scp -r deploy/ root@SERVIDOR:/tmp/deploy
ssh root@SERVIDOR
bash /tmp/deploy/provision.sh
```

Instala PHP 8.4 (repo Sury), nginx, PostgreSQL 16, cria o utilizador
`deploy` e o diretório da aplicação, liga a firewall e as atualizações de
segurança automáticas. É idempotente: podes voltar a correr.

No fim imprime a password gerada para a base de dados. **Copia-a agora** —
não fica escrita em lado nenhum a não ser no `.env` que vais criar a seguir.

## Passo 2 — o certificado

```bash
mkdir -p /etc/ssl/cloudflare
nano /etc/ssl/cloudflare/origin.pem    # cola o certificado
nano /etc/ssl/cloudflare/origin.key    # cola a chave privada
chmod 600 /etc/ssl/cloudflare/origin.key
chown root:root /etc/ssl/cloudflare/*
```

## Passo 3 — nginx

```bash
cp /tmp/deploy/nginx/site.conf /etc/nginx/sites-available/fiestas
ln -sf /etc/nginx/sites-available/fiestas /etc/nginx/sites-enabled/fiestas
rm -f /etc/nginx/sites-enabled/default

nano /etc/nginx/sites-available/fiestas    # troca DOMINIO.TLD pelo teu

cp /tmp/deploy/update-cloudflare-ips.sh /usr/local/sbin/
chmod +x /usr/local/sbin/update-cloudflare-ips.sh
/usr/local/sbin/update-cloudflare-ips.sh

nginx -t && systemctl reload nginx
```

O `update-cloudflare-ips.sh` vai buscar a lista de intervalos da Cloudflare
e escreve `/etc/nginx/conf.d/10-cloudflare.conf`. Corre sozinho todas as
semanas (ver passo 6): se a Cloudflare acrescentar intervalos e a lista
ficar velha, o site fica inacessível.

## Passo 4 — a aplicação

```bash
su - deploy
git clone O_TEU_REPO /var/www/fiestas
cd /var/www/fiestas

cp deploy/.env.production.example .env
nano .env                       # domínio, password da BD, chaves
php artisan key:generate
```

Depois, ainda como `deploy`:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=CatalogSeeder --force
php artisan optimize
php artisan make:filament-user
```

## Passo 5 — permissões

Como root:

```bash
chown -R deploy:www-data /var/www/fiestas
find /var/www/fiestas/storage /var/www/fiestas/bootstrap/cache -type d -exec chmod 775 {} +
find /var/www/fiestas/storage /var/www/fiestas/bootstrap/cache -type f -exec chmod 664 {} +
```

O PHP-FPM corre como `www-data` e só precisa de escrever em `storage/` e
`bootstrap/cache/`. Em mais lado nenhum. Se um dia alguém encontrar uma
falha na aplicação, isto é a diferença entre estragarem uma cache e
reescreverem o código do site.

## Passo 6 — serviços de fundo

```bash
cp /tmp/deploy/systemd/* /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now fiestas-queue.service
systemctl enable --now fiestas-scheduler.timer
systemctl enable --now fiestas-backup.timer
systemctl enable --now cloudflare-ips.timer
```

- **queue** — emails e trabalhos em fundo. Usa a base de dados como fila,
  de propósito: um Redis a mais é um serviço a mais para manter, e a esta
  escala não ganha nada.
- **scheduler** — corre `schedule:run` a cada minuto.
- **backup** — cópia diária às 4 da manhã (ver `backup.sh`).
- **cloudflare-ips** — atualiza a lista de intervalos semanalmente.

## Passo 7 — Cloudflare

No painel:

- SSL/TLS → **Full (strict)**
- SSL/TLS → Edge Certificates → **Always Use HTTPS** ligado
- DNS → registo `A` do domínio a apontar para o servidor, **com a nuvem
  laranja ligada** (sem isso, nada do que está aqui protege coisa nenhuma)

## Passo 8 — confirmar que está mesmo fechado

```bash
curl -sI https://DOMINIO.TLD | head -1                 # 200
curl -sI --resolve DOMINIO.TLD:443:IP_DO_SERVIDOR \
     https://DOMINIO.TLD | head -1                     # 403
```

A segunda tem de dar **403**. Se der 200, o servidor está a aceitar
ligações diretas e toda a proteção da Cloudflare é decorativa — qualquer
pessoa lhe chega ao lado.

Confirma também que os IPs chegam certos: submete o formulário do site e vê

```sql
SELECT ip_hash, created_at FROM leads ORDER BY id DESC LIMIT 5;
```

Se dois pedidos de sítios diferentes derem o mesmo hash, o passo 3 não está
a funcionar.

---

## Atualizar depois

```bash
su - deploy
cd /var/www/fiestas
bash deploy/deploy.sh
```

## Cópias de segurança

O `backup.sh` guarda em `/var/backups/fiestas`: um `pg_dump` e um arquivo do
`storage/app`. Mantém 14 diárias e 8 semanais.

**Uma cópia que nunca foi restaurada não é uma cópia de segurança.** Uma vez
por mês, a sério:

```bash
bash deploy/restore.sh /var/backups/fiestas/diario/AAAA-MM-DD.dump --dry-run
```

O `--dry-run` restaura para uma base de dados descartável, conta as linhas
e apaga-a. Não toca na produção.

E leva uma cópia para fora do servidor — um disco que arde leva as cópias
com ele. O mais simples, sem custos: do teu PC,

```bash
rsync -az --delete deploy@SERVIDOR:/var/backups/fiestas/ ~/backups/fiestas/
```

---

## Quando alguma coisa correr mal

**O site dá 403 a toda a gente.** A lista de intervalos da Cloudflare está
vazia ou desatualizada. `cat /etc/nginx/conf.d/10-cloudflare.conf` e conta as
linhas; se forem poucas, corre o `update-cloudflare-ips.sh` à mão. Se a
nuvem laranja no DNS estiver desligada, o tráfego chega sem passar pela
Cloudflare e é recusado — e está a fazer o que deve.

**Mudei código e o site continua igual.** É o opcache com
`validate_timestamps=0`. `sudo systemctl restart php8.4-fpm`. O `deploy.sh`
já o faz; a mão esquece-se.

**Um pacote queixa-se de `proc_open` ou `allow_url_fopen`.** Estão fechados
no `php/fiestas-pool.conf` de propósito — são as duas ferramentas mais úteis
para quem encontrar uma falha na aplicação. Antes de as reabrires, vê se o
pacote precisa mesmo delas em produção ou só nas ferramentas de linha de
comandos (que correm no PHP-CLI e não são afetadas).

**Todos os leads com o mesmo `ip_hash`.** O passo 3 não está a funcionar: o
Laravel está a ver o IP da Cloudflare em vez do visitante. Confirma que o
`10-cloudflare.conf` tem os `set_real_ip_from` **e** o
`real_ip_header CF-Connecting-IP`.

**Ficaste sem base de dados.** `deploy/restore.sh` com a cópia mais recente.
Se nunca fizeste o ensaio mensal, é agora que descobres se as cópias prestam
— e é o pior momento possível para descobrir.

---

## O que aqui NÃO está

Coisas que fazem falta e que não vale a pena fingir que estão resolvidas:

- **Monitorização.** Não há nada que avise se o site cair às 3 da manhã.
  Uma verificação externa gratuita (o Uptime Kuma numa máquina tua, ou a
  verificação de saúde da própria Cloudflare) resolve, mas é uma decisão a
  tomar, não um ficheiro a copiar.
- **Segundo servidor.** É uma máquina só. Se arder, o site fica em baixo
  até haver outra. As cópias de segurança levadas para fora protegem os
  dados, não o tempo de reposição.
- **Emails a sério.** Enquanto `MAIL_MAILER=log`, ninguém recebe nada. É a
  decisão seguinte: um SMTP que já tenhas, ou um serviço com nível
  gratuito.
