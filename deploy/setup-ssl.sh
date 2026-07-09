#!/usr/bin/env bash
set -euo pipefail

# Install free SSL (Let's Encrypt) and enable HTTPS for the Laravel app.
# Run on the server as a user with sudo:
#   sudo bash /var/www/viverider/deploy/setup-ssl.sh viberidegh.online

DOMAIN="${1:-viberidegh.online}"
WWW_DOMAIN="www.${DOMAIN}"
APP_DIR="${APP_DIR:-/var/www/viverider}"
EMAIL="${SSL_EMAIL:-admin@${DOMAIN}}"

cd "${APP_DIR}"

echo "==> Domain: ${DOMAIN}"
echo "==> App dir: ${APP_DIR}"

SERVER_IP="$(curl -4 -s ifconfig.me || curl -4 -s icanhazip.com || true)"
DNS_IP="$(dig +short "${DOMAIN}" A 2>/dev/null | tail -1 || true)"

echo "==> Server public IP: ${SERVER_IP:-unknown}"
echo "==> DNS ${DOMAIN} → ${DNS_IP:-not found}"

if [ -n "${SERVER_IP}" ] && [ -n "${DNS_IP}" ] && [ "${SERVER_IP}" != "${DNS_IP}" ]; then
  echo "WARNING: DNS does not point to this server."
  echo "  Fix your domain A record: ${DOMAIN} → ${SERVER_IP}"
  echo "  Continue anyway only if DNS was just changed (may take up to 30 min)."
fi

echo "==> Checking ports 80 and 443..."
if ! sudo ss -ltn | grep -q ':80 '; then
  echo "ERROR: Nothing listening on port 80. Start nginx first."
  exit 1
fi

if ! command -v certbot >/dev/null 2>&1; then
  echo "==> Installing Certbot..."
  sudo apt-get update -qq
  sudo apt-get install -y certbot python3-certbot-nginx
fi

echo "==> Applying base nginx config..."
sudo cp deploy/nginx-viverider.conf /etc/nginx/sites-available/viberidegh.online
sudo ln -sf /etc/nginx/sites-available/viberidegh.online /etc/nginx/sites-enabled/viberidegh.online
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

echo "==> Requesting SSL certificate (Let's Encrypt)..."
sudo certbot --nginx \
  -d "${DOMAIN}" \
  -d "${WWW_DOMAIN}" \
  --non-interactive \
  --agree-tos \
  -m "${EMAIL}" \
  --redirect

echo "==> Updating Laravel .env for HTTPS..."
if [ -f .env ]; then
  if grep -q '^APP_URL=' .env; then
    sudo sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
  else
    echo "APP_URL=https://${DOMAIN}" | sudo tee -a .env
  fi
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
echo "SSL setup complete."
echo "  https://${DOMAIN}"
echo ""
echo "Also update GitHub SERVER_ENV secret:"
echo "  APP_URL=https://${DOMAIN}"
echo "  SESSION_SECURE_COOKIE=true"
echo "  SESSION_DOMAIN=${DOMAIN}"
echo ""
echo "Renewal test: sudo certbot renew --dry-run"
