#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

bash scripts/use-local-env.sh

export DB_DATABASE="${DB_DATABASE:-viverider}"
export DB_USERNAME="${DB_USERNAME:-viverider_usr}"
export DB_PASSWORD="${DB_PASSWORD:-VvrDb2026_Aq9mL7xP2}"
export DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"
export APP_ENV=local
export NODE_ENV=development

echo "==> MySQL local (3308)..."
docker compose -f docker-compose.db.yml -f docker-compose.db.local.yml up -d

for i in $(seq 1 30); do
  if docker exec viverider-mysql-local mysqladmin ping -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

php artisan storage:link --force 2>/dev/null || ln -sfn "$ROOT/storage/app/public" "$ROOT/public/storage"
php artisan config:clear

echo ""
echo "Development ready (APP_ENV=local, NODE_ENV=development)"
echo "  php artisan serve"
echo "  npm run dev"
echo "  http://127.0.0.1:8000"
