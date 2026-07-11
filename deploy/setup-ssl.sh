#!/usr/bin/env bash
set -euo pipefail

# Install free SSL (Let's Encrypt) and enable HTTPS.
#   sudo SSL_EMAIL=you@email.com bash /var/www/viverider/deploy/setup-ssl.sh viberidegh.online
# Do NOT fall back to self-signed for public domain (browsers block it).

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

# HTTP first so ACME challenge works
sudo cp deploy/nginx-viverider.conf /etc/nginx/sites-available/viberidegh.online
sudo ln -sf /etc/nginx/sites-available/viberidegh.online /etc/nginx/sites-enabled/viberidegh.online
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
  [ -f /etc/letsencrypt/ssl-dhparams.pem ] || sudo openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048
  if ! sudo certbot certonly --webroot -w "${APP_DIR}/public" \
    -d "${DOMAIN}" -d "${WWW_DOMAIN}" \
    --non-interactive --agree-tos -m "${EMAIL}"; then
    sudo certbot certonly --webroot -w "${APP_DIR}/public" \
      -d "${DOMAIN}" \
      --non-interactive --agree-tos -m "${EMAIL}"
  fi
fi

if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
  echo "ERROR: Let's Encrypt failed. Domain stays on HTTP."
  echo "Check: DNS A record → this server, port 80 open, SSL_EMAIL valid."
  exit 1
fi

sudo cp deploy/nginx-viberidegh.ssl.conf /etc/nginx/sites-available/viberidegh.online
sudo nginx -t
sudo systemctl reload nginx

if command -v ufw >/dev/null 2>&1 && sudo ufw status 2>/dev/null | grep -qi "Status: active"; then
  sudo ufw allow 'Nginx Full' 2>/dev/null || { sudo ufw allow 80/tcp; sudo ufw allow 443/tcp; }
fi

if [ -f .env ]; then
  sudo sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  grep -q '^SESSION_SECURE_COOKIE=' .env \
    && sudo sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env \
    || echo 'SESSION_SECURE_COOKIE=true' | sudo tee -a .env
  grep -q '^SESSION_DOMAIN=' .env \
    && sudo sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=${DOMAIN}|" .env \
    || echo "SESSION_DOMAIN=${DOMAIN}" | sudo tee -a .env
  sudo -u www-data php artisan config:cache
fi

echo "HTTPS ready: https://${DOMAIN}"
