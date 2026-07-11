#!/usr/bin/env bash
# Runs on the production server after code upload.
# Usage: bash deploy/remote-deploy.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/viverider}"
DOMAIN="${APP_DOMAIN:-viberidegh.online}"
cd "$APP_DIR"

read_env() {
  grep -E "^${1}=" .env 2>/dev/null | head -n1 | cut -d= -f2- \
    | sed 's/^"\(.*\)"$/\1/' | sed "s/^'\(.*\)'$/\1/" | tr -d '\r' || true
}

echo "==> PHP $(php -r 'echo PHP_VERSION;')"

if [ ! -f .env ]; then
  if [ -f deploy/production.env ]; then
    cp deploy/production.env .env
  else
    echo "ERROR: .env missing. Upload .env from CI or commit deploy/production.env"
    exit 1
  fi
fi

# Normalize DB host for host-network PHP → Docker MySQL
if [ "$(read_env DB_HOST)" = "mysql" ]; then
  sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
fi

APP_URL="$(read_env APP_URL)"
APP_HOST="$(echo "${APP_URL}" | sed -E 's#https?://##' | sed 's#/.*##' | sed 's/^www\.//')"
APP_HOST="${APP_HOST:-$DOMAIN}"

grep -q '^SESSION_DOMAIN=' .env \
  && sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${APP_HOST}|" .env \
  || echo "SESSION_DOMAIN=${APP_HOST}" >> .env

DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"
DB_ROOT_PASSWORD="$(read_env DB_ROOT_PASSWORD)"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"
SSL_EMAIL="$(read_env SSL_EMAIL)"

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ]; then
  echo "ERROR: Missing DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env"
  exit 1
fi

export DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD

echo "==> MySQL (Docker)"
sudo systemctl stop mysql 2>/dev/null || true
if docker compose version >/dev/null 2>&1; then
  DC="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  DC="docker-compose"
else
  echo "ERROR: docker compose required"; exit 1
fi
$DC -f docker-compose.db.yml up -d

for i in $(seq 1 30); do
  if docker exec viverider-mysql mysqladmin ping -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; then
    echo "MySQL ready"; break
  fi
  [ "$i" -eq 30 ] && { docker logs viverider-mysql --tail 50 || true; exit 1; }
  sleep 2
done

SEED_LOCK="/var/www/.viverider-db-seeded"
if [ ! -f "${SEED_LOCK}" ] && [ -f database/database.sql ]; then
  HAS_TABLE="$(docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='landing_homes';" 2>/dev/null || echo 0)"
  ROWS=0
  if [ "${HAS_TABLE}" = "1" ]; then
    ROWS="$(docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
      "SELECT COUNT(*) FROM ${DB_DATABASE}.landing_homes;" 2>/dev/null || echo 0)"
  fi
  if [ "${ROWS}" = "0" ]; then
    echo "==> Seeding database..."
    docker exec viverider-mysql mysql -h 127.0.0.1 -u root -p"${DB_ROOT_PASSWORD}" -e \
      "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'; FLUSH PRIVILEGES;"
    sed -e "s/\`taxi1\`/\`${DB_DATABASE}\`/g" -e '/^CREATE DATABASE IF NOT EXISTS/d' -e '/^USE `/d' \
      database/database.sql | docker exec -i viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"
  fi
  sudo touch "${SEED_LOCK}"
fi

echo "==> Storage + permissions"
sudo mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public/uploads bootstrap/cache
if [ -d /var/www/.viverider-persist/uploads ]; then
  sudo rsync -a /var/www/.viverider-persist/uploads/ storage/app/public/uploads/
fi
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
sudo rm -f public/storage
sudo ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"
sudo chown -h www-data:www-data public/storage

sudo -u www-data php artisan package:discover --ansi
sudo -u www-data php artisan migrate --force

if [ -f deploy/rebrand-viberide.sql ]; then
  docker exec -i viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" \
    < deploy/rebrand-viberide.sql || true
  sudo -u www-data php artisan cache:clear || true
fi

echo "==> Nginx + SSL"
NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"
CERT="/etc/letsencrypt/live/${APP_HOST}/fullchain.pem"

sudo cp deploy/nginx-viverider.conf "${NGINX_SITE}"
sudo ln -sf "${NGINX_SITE}" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

if [ ! -f "${CERT}" ] && [ -n "${SSL_EMAIL}" ]; then
  sudo apt-get update -qq
  sudo apt-get install -y certbot python3-certbot-nginx ssl-cert
  [ -f /etc/letsencrypt/ssl-dhparams.pem ] || sudo openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048
  sudo certbot certonly --webroot -w "${APP_DIR}/public" -d "${APP_HOST}" -d "www.${APP_HOST}" \
    --non-interactive --agree-tos -m "${SSL_EMAIL}" \
    || sudo certbot certonly --webroot -w "${APP_DIR}/public" -d "${APP_HOST}" \
         --non-interactive --agree-tos -m "${SSL_EMAIL}" || true
fi

if [ -f "${CERT}" ]; then
  sudo cp deploy/nginx-viberidegh.ssl.conf "${NGINX_SITE}"
  sed -i "s|^APP_URL=.*|APP_URL=https://${APP_HOST}|" .env
  sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
elif [ -f /etc/ssl/certs/ssl-cert-snakeoil.pem ] || sudo apt-get install -y ssl-cert; then
  sudo cp deploy/nginx-viberidegh.ssl-snakeoil.conf "${NGINX_SITE}"
  sed -i "s|^APP_URL=.*|APP_URL=https://${APP_HOST}|" .env
  sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
else
  sed -i "s|^APP_URL=.*|APP_URL=http://${APP_HOST}|" .env
  sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/' .env
fi

sudo nginx -t
command -v ufw >/dev/null && sudo ufw status 2>/dev/null | grep -qi active \
  && { sudo ufw allow 'Nginx Full' 2>/dev/null || { sudo ufw allow 80/tcp; sudo ufw allow 443/tcp; }; } || true

echo "==> Cache + reload"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.5-fpm
sudo systemctl reload nginx

APP_URL="$(read_env APP_URL)"
echo "Deploy complete: ${APP_URL}"
