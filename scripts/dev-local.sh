#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

bash scripts/use-local-env.sh

export DB_DATABASE="${DB_DATABASE:-viverider}"
export DB_USERNAME="${DB_USERNAME:-viverider_usr}"
export DB_PASSWORD="${DB_PASSWORD:-VvrDb2026_Aq9mL7xP2}"
export DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"

echo "==> Starting LOCAL MySQL (port 3308, volume: viverider_mysql_data_local)..."
docker compose -f docker-compose.db.yml -f docker-compose.db.local.yml up -d

echo "==> Waiting for MySQL..."
for i in $(seq 1 30); do
  if docker exec viverider-mysql-local mysqladmin ping -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "==> Storage symlink..."
php artisan storage:link --force 2>/dev/null || ln -sfn "$ROOT/storage/app/public" "$ROOT/public/storage"

if [ -f deploy/rebrand-viberide.sql ]; then
  docker exec -i viverider-mysql-local mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" < deploy/rebrand-viberide.sql 2>/dev/null || true
fi

echo ""
echo "Local stack ready (data stays on this machine only):"
echo "  ./scripts/dev-local.sh   # already ran"
echo "  php artisan serve"
echo "  npm run dev"
echo "  http://127.0.0.1:8000"
