#!/usr/bin/env bash
# =============================================================================
# Viverider — AWS EC2 Docker deployment script
# Run on Ubuntu 22.04/24.04 EC2 instance
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/viverider}"
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "=============================================="
echo " Viverider Docker Deploy"
echo " App dir: ${APP_DIR}/website"
echo "=============================================="

# --- Install Docker ---
if ! command -v docker &>/dev/null; then
    echo "==> Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    sudo usermod -aG docker "$USER" 2>/dev/null || true
    systemctl enable docker
    systemctl start docker
fi

cd "${APP_DIR}/website"

# --- .env check ---
if [ ! -f .env ]; then
    cp .env.docker.example .env
    echo ""
    echo "!! Created .env — set these values first:"
    echo "   DB_PASSWORD, DB_ROOT_PASSWORD, APP_KEY"
    echo "   nano .env"
    echo ""
    echo "   Generate APP_KEY: docker compose run --rm app php artisan key:generate --show"
    exit 1
fi

# --- Build & start ---
echo "==> Building images (this may take 5-10 minutes)..."
${COMPOSE} up -d --build --remove-orphans

echo "==> Container status:"
sleep 10
${COMPOSE} ps

echo ""
echo "=============================================="
echo " Deploy complete!"
echo ""
echo " Site:    http://www.viverider.online"
echo " Admin:   http://www.viverider.online/login/admin"
echo ""
echo " Next steps:"
echo "   1. Point DNS A records to this server IP"
echo "   2. Import DB:  docker compose exec -T mysql mysql -u viverider -p viverider < /tmp/database.txt"
echo "   3. Enable SSL: bash deploy/ssl-init.sh"
echo ""
echo " Logs:    ${COMPOSE} logs -f app"
echo " Restart: ${COMPOSE} restart"
echo "=============================================="
