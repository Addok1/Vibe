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

# Seed empty local DB once (same dump as production)
HAS_SETTINGS="$(docker exec viverider-mysql-local mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='settings';" 2>/dev/null || echo 0)"
if [ "${HAS_SETTINGS}" != "1" ] && [ -f database/database.sql ]; then
  echo "==> Seeding local database from database/database.sql..."
  docker exec viverider-mysql-local mysql -h 127.0.0.1 -u root -p"${DB_ROOT_PASSWORD}" -e \
    "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'; FLUSH PRIVILEGES;"
  sed -e "s/\`taxi1\`/\`${DB_DATABASE}\`/g" -e '/^CREATE DATABASE IF NOT EXISTS/d' -e '/^USE `/d' \
    database/database.sql | docker exec -i viverider-mysql-local mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"
  if [ -f deploy/rebrand-viberide.sql ]; then
    docker exec -i viverider-mysql-local mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" \
      < deploy/rebrand-viberide.sql || true
  fi
fi

php artisan storage:link --force 2>/dev/null || ln -sfn "$ROOT/storage/app/public" "$ROOT/public/storage"
php artisan migrate --force --no-interaction 2>/dev/null || true
php artisan config:clear
php artisan cache:clear 2>/dev/null || true

echo ""
echo "Development ready (APP_ENV=local, NODE_ENV=development, SESSION_DRIVER=database)"
echo "  php artisan serve"
echo "  npm run dev"
echo "  http://127.0.0.1:8000"
