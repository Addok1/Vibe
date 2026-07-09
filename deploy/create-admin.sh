#!/usr/bin/env bash
set -euo pipefail

# Create or reset the default super-admin user in Docker MySQL.
# Run on the server: sudo bash /var/www/viverider/deploy/create-admin.sh

APP_DIR="${APP_DIR:-/var/www/viverider}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@admin.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-123456789}"
ADMIN_NAME="${ADMIN_NAME:-admin}"
ADMIN_MOBILE="${ADMIN_MOBILE:-9999999999}"

cd "${APP_DIR}"

read_env() {
  grep -E "^${1}=" .env 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '\r' || true
}

DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"

if [[ -z "${DB_DATABASE}" || -z "${DB_USERNAME}" || -z "${DB_PASSWORD}" ]]; then
  echo "Missing DB_* values in ${APP_DIR}/.env" >&2
  exit 1
fi

ARTISAN_USER="www-data"
if ! id "${ARTISAN_USER}" >/dev/null 2>&1; then
  ARTISAN_USER="$(whoami)"
fi

run_artisan() {
  if [[ "${ARTISAN_USER}" == "$(whoami)" ]]; then
    php artisan "$@"
  else
    sudo -u "${ARTISAN_USER}" php artisan "$@"
  fi
}

if ! docker ps --format '{{.Names}}' | grep -qx 'viverider-mysql'; then
  echo "Container viverider-mysql is not running. Start it first:" >&2
  echo "  cd ${APP_DIR} && docker compose -f docker-compose.db.yml up -d" >&2
  exit 1
fi

echo "Creating/updating admin: ${ADMIN_EMAIL}"

run_artisan db:seed --class=AdminSeeder --force

HASHED="$(php -r "echo password_hash('${ADMIN_PASSWORD}', PASSWORD_BCRYPT);")"

docker exec viverider-mysql mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
UPDATE users
SET password = '${HASHED}', active = 1, mobile_confirmed = 1
WHERE email = '${ADMIN_EMAIL}';

INSERT IGNORE INTO role_user (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'super-admin'
WHERE u.email = '${ADMIN_EMAIL}';
"

docker exec viverider-mysql mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
SELECT u.id, u.email, u.active, r.slug AS role
FROM users u
LEFT JOIN role_user ru ON ru.user_id = u.id
LEFT JOIN roles r ON r.id = ru.role_id
WHERE u.email = '${ADMIN_EMAIL}';
"

echo ""
echo "Admin ready."
echo "  URL:      http://viverider.online/mi-admin"
echo "  Email:    ${ADMIN_EMAIL}"
echo "  Password: ${ADMIN_PASSWORD}"
