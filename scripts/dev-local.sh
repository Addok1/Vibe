#!/usr/bin/env bash
set -euo pipefail

# Local dev: MySQL (Docker) + Laravel + Vite
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

export DB_DATABASE="${DB_DATABASE:-viverider}"
export DB_USERNAME="${DB_USERNAME:-viverider_usr}"
export DB_PASSWORD="${DB_PASSWORD:-VvrDb2026_Aq9mL7xP2}"
export DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"

echo "==> Starting MySQL on port 3308..."
docker compose -f docker-compose.db.yml -f docker-compose.db.local.yml up -d

echo "==> Storage symlink..."
php artisan storage:link --force 2>/dev/null || ln -sfn "$ROOT/storage/app/public" "$ROOT/public/storage"

echo "==> Clear config cache..."
php artisan config:clear

echo ""
echo "Run in separate terminals:"
echo "  php artisan serve"
echo "  npm run dev"
echo ""
echo "Or: composer dev"
echo "Site: http://127.0.0.1:8000"
