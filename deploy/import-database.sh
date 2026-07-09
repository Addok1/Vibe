#!/usr/bin/env bash
set -euo pipefail

cd /var/www/viverider

read_env() {
  grep -E "^${1}=" .env 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '\r' || true
}

DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"
DB_ROOT_PASSWORD="$(read_env DB_ROOT_PASSWORD)"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"

echo "Resetting and importing ${DB_DATABASE}..."

docker exec viverider-mysql mysql -u root -p"${DB_ROOT_PASSWORD}" -e "
DROP DATABASE IF EXISTS \`${DB_DATABASE}\`;
CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
"

sed -e "s/\`taxi1\`/\`${DB_DATABASE}\`/g" \
    -e '/^CREATE DATABASE IF NOT EXISTS/d' \
    -e '/^USE `/d' \
    database/database.sql | docker exec -i viverider-mysql mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"

mysql -h 127.0.0.1 -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
SELECT COUNT(*) AS landing_homes FROM landing_homes;
SELECT COUNT(*) AS single_landing FROM single_landing_page;
"

sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache

sudo touch /var/www/.viverider-db-seeded

echo "Done. Seed lock: /var/www/.viverider-db-seeded"
