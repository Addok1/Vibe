#!/usr/bin/env bash
# Optional: test production .env locally (still uses local DB on 3308 unless you change DB_PORT)
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp .env.prod .env
# Keep local MySQL port when testing prod config on laptop
if grep -q '^DB_PORT=3306' .env; then
  sed -i.bak 's/^DB_PORT=3306/DB_PORT=3308/' .env && rm -f .env.bak
fi
sed -i.bak 's|^APP_URL=.*|APP_URL=http://127.0.0.1:8000|' .env && rm -f .env.bak
sed -i.bak 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/' .env && rm -f .env.bak
sed -i.bak 's/^SESSION_DOMAIN=.*/SESSION_DOMAIN=/' .env && rm -f .env.bak
sed -i.bak 's|^FIREBASE_CREDENTIALS=.*|FIREBASE_CREDENTIALS=public/push-configurations/firebase.json|' .env && rm -f .env.bak

php artisan config:clear
echo "Active env: PROD template locally (.env.prod -> .env, URLs forced to 127.0.0.1:8000)"
