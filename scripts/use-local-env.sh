#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

cp .env.local .env
php artisan config:clear
php artisan cache:clear 2>/dev/null || true

echo "Active env: LOCAL (.env.local -> .env)"
echo "  APP_URL=http://127.0.0.1:8000"
echo "  DB_PORT=3308 (Docker, separate from production data)"
