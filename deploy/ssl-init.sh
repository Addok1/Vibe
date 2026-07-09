#!/usr/bin/env bash
# =============================================================================
# Obtain Let's Encrypt SSL and enable HTTPS nginx config
# Run from: /opt/viverider/website
# =============================================================================
set -euo pipefail

DOMAIN="${DOMAIN:-viverider.online}"
EMAIL="${EMAIL:-admin@viverider.online}"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> Obtaining SSL for ${DOMAIN} and www.${DOMAIN}..."

${COMPOSE} run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email "${EMAIL}" \
    --agree-tos \
    --no-eff-email \
    -d "${DOMAIN}" \
    -d "www.${DOMAIN}"

echo "==> Enabling HTTPS nginx config..."

# Switch nginx from HTTP-only to HTTPS config
if grep -q "default.http.conf" docker-compose.yml; then
    sed -i.bak 's|default.http.conf|default.conf|g' docker-compose.yml
    echo "    Updated docker-compose.yml to use default.conf"
fi

${COMPOSE} up -d nginx

echo "==> SSL ready: https://www.${DOMAIN}"
echo "    Renew: ${COMPOSE} run --rm certbot renew"
