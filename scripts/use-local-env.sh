#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp .env.local .env
# Accept APP_ENV=development as alias for local
if [ "${APP_ENV:-}" = "development" ] || [ "${NODE_ENV:-}" = "development" ]; then
  sed -i.bak 's/^APP_ENV=.*/APP_ENV=local/' .env && rm -f .env.bak
  sed -i.bak 's/^NODE_ENV=.*/NODE_ENV=development/' .env && rm -f .env.bak
fi

php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "Active: DEVELOPMENT/LOCAL"
echo "  APP_ENV=$(grep ^APP_ENV= .env | cut -d= -f2)"
echo "  NODE_ENV=$(grep ^NODE_ENV= .env | cut -d= -f2)"
echo "  APP_URL=$(grep ^APP_URL= .env | cut -d= -f2)"
echo "  DB_PORT=$(grep ^DB_PORT= .env | cut -d= -f2)"
