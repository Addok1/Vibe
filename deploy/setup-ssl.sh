#!/usr/bin/env bash
set -euo pipefail

# Install free SSL (Let's Encrypt) and enable HTTPS for the Laravel app.
# Run on the server:
#   sudo SSL_EMAIL=you@email.com bash /var/www/viverider/deploy/setup-ssl.sh viberidegh.online

DOMAIN="${1:-viberidegh.online}"
WWW_DOMAIN="www.${DOMAIN}"
APP_DIR="${APP_DIR:-/var/www/viverider}"
EMAIL="${SSL_EMAIL:-admin@${DOMAIN}}"

cd "${APP_DIR}"

echo "==> Domain: ${DOMAIN}"

if ! command -v certbot >/dev/null 2>&1; then
  sudo apt-get update -qq
  sudo apt-get install -y certbot python3-certbot-nginx
fi

sudo cp deploy/nginx-viverider.conf /etc/nginx/sites-available/viberidegh.online
sudo ln -sf /etc/nginx/sites-available/viberidegh.online /etc/nginx/sites-enabled/viberidegh.online
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
  if [ ! -f /etc/letsencrypt/ssl-dhparams.pem ]; then
    sudo openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048
  fi
  sudo certbot certonly --webroot -w "${APP_DIR}/public" \
    -d "${DOMAIN}" -d "${WWW_DOMAIN}" \
    --non-interactive --agree-tos -m "${EMAIL}"
fi

sudo cp deploy/nginx-viberidegh.ssl.conf /etc/nginx/sites-available/viberidegh.online
sudo nginx -t
sudo systemctl reload nginx

if command -v ufw >/dev/null 2>&1 && sudo ufw status 2>/dev/null | grep -qi "Status: active"; then
  sudo ufw allow 'Nginx Full' 2>/dev/null || { sudo ufw allow 80/tcp; sudo ufw allow 443/tcp; }
fi

if [ -f .env ]; then
  sudo sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  if grep -q '^SESSION_SECURE_COOKIE=' .env; then
    sudo sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
  else
    echo 'SESSION_SECURE_COOKIE=true' | sudo tee -a .env
  fi
  if grep -q '^SESSION_DOMAIN=' .env; then
    sudo sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${DOMAIN}|" .env
  else
    echo "SESSION_DOMAIN=${DOMAIN}" | sudo tee -a .env
  fi
  sudo -u www-data php artisan config:cache
fi

echo ""
echo "HTTPS ready: https://${DOMAIN}"
echo "Update GitHub SERVER_ENV: APP_URL=https://${DOMAIN} and SESSION_SECURE_COOKIE=true"
