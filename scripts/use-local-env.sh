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

# Same session rules as production (prevents local 419 / session expired)
grep -q '^SESSION_DRIVER=' .env && sed -i.bak 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env && rm -f .env.bak || echo 'SESSION_DRIVER=database' >> .env
grep -q '^SESSION_COOKIE=' .env && sed -i.bak 's/^SESSION_COOKIE=.*/SESSION_COOKIE=viberide_session/' .env && rm -f .env.bak || echo 'SESSION_COOKIE=viberide_session' >> .env
grep -q '^SESSION_SAME_SITE=' .env && sed -i.bak 's/^SESSION_SAME_SITE=.*/SESSION_SAME_SITE=lax/' .env && rm -f .env.bak || echo 'SESSION_SAME_SITE=lax' >> .env
sed -i.bak '/^SESSION_DOMAIN=/d' .env && rm -f .env.bak
sed -i.bak '/^SESSION_SECURE_COOKIE=/d' .env && rm -f .env.bak

php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "Active: DEVELOPMENT/LOCAL"
echo "  APP_ENV=$(grep ^APP_ENV= .env | cut -d= -f2)"
echo "  NODE_ENV=$(grep ^NODE_ENV= .env | cut -d= -f2)"
echo "  APP_URL=$(grep ^APP_URL= .env | cut -d= -f2)"
echo "  DB_PORT=$(grep ^DB_PORT= .env | cut -d= -f2)"
echo "  SESSION_DRIVER=$(grep ^SESSION_DRIVER= .env | cut -d= -f2)"
