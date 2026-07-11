#!/usr/bin/env bash
# Switch active .env to production template (for inspecting prod config locally).
# Still uses local DB port 3308 so laptop MySQL keeps working.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp deploy/production.env .env
sed -i.bak 's/^DB_PORT=.*/DB_PORT=3308/' .env && rm -f .env.bak
sed -i.bak 's|^APP_URL=.*|APP_URL=http://127.0.0.1:8000|' .env && rm -f .env.bak
sed -i.bak 's|^FIREBASE_CREDENTIALS=.*|FIREBASE_CREDENTIALS=public/push-configurations/firebase.json|' .env && rm -f .env.bak
# Keep APP_ENV=production for testing prod code paths, or force local:
# sed -i.bak 's/^APP_ENV=.*/APP_ENV=local/' .env

php artisan config:clear 2>/dev/null || true
echo "Active: production.env (DB_PORT forced to 3308 for laptop)"
grep -E '^(APP_ENV|NODE_ENV|APP_URL|DB_PORT)=' .env
