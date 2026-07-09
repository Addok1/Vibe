#!/usr/bin/env bash
# Run on production server: sudo bash deploy/fix-production.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/viverider}"
DOMAIN="${1:-viberidegh.online}"
cd "$APP_DIR"

read_env() {
  grep -E "^${1}=" .env 2>/dev/null | head -n1 | cut -d= -f2- | sed 's/^"\(.*\)"$/\1/' | sed "s/^'\(.*\)'$/\1/" | tr -d '\r' || true
}

DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"

echo "==> Apply branding/content SQL..."
docker exec -i viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" < deploy/rebrand-viberide.sql

echo "==> Storage symlink..."
sudo rm -f public/storage
sudo ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"
sudo chown -h www-data:www-data public/storage

echo "==> SSL check..."
CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"

if [ -f "$CERT" ]; then
  sudo cp deploy/nginx-viberidegh.ssl.conf "$NGINX_SITE"
  sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  grep -q '^SESSION_SECURE_COOKIE=' .env && sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env || echo 'SESSION_SECURE_COOKIE=true' >> .env
  echo "HTTPS enabled."
else
  echo "No cert at $CERT — HTTP only."
  sudo cp deploy/nginx-viverider.conf "$NGINX_SITE"
  sed -i "s|^APP_URL=.*|APP_URL=http://${DOMAIN}|" .env
  grep -q '^SESSION_SECURE_COOKIE=' .env && sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/' .env || echo 'SESSION_SECURE_COOKIE=false' >> .env
  if [ -n "${SSL_EMAIL:-}" ]; then
    sudo certbot certonly --webroot -w "${APP_DIR}/public" -d "${DOMAIN}" -d "www.${DOMAIN}" \
      --non-interactive --agree-tos -m "${SSL_EMAIL}" || true
    if [ -f "$CERT" ]; then
      sudo cp deploy/nginx-viberidegh.ssl.conf "$NGINX_SITE"
      sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
      sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
    fi
  fi
fi

sudo ln -sf "$NGINX_SITE" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo nginx -t
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:clear
sudo systemctl reload php8.5-fpm
sudo systemctl reload nginx

echo "==> Done. Test:"
echo "  curl -I http://${DOMAIN}/"
echo "  curl -Ik https://${DOMAIN}/"
