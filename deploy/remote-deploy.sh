#!/usr/bin/env bash
# Production server post-upload. Does NOT touch nginx (you manage SSL/nginx yourself).
# Usage: bash deploy/remote-deploy.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/viverider}"
cd "$APP_DIR"

read_env() {
  grep -E "^${1}=" .env 2>/dev/null | head -n1 | cut -d= -f2- \
    | sed 's/^"\(.*\)"$/\1/' | sed "s/^'\(.*\)'$/\1/" | tr -d '\r' || true
}

ensure_session_env() {
  # Database sessions survive deploy wipes; host-only cookies work on IP + domain
  grep -q '^SESSION_DRIVER=' .env && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env || echo 'SESSION_DRIVER=database' >> .env
  grep -q '^SESSION_LIFETIME=' .env && sed -i 's/^SESSION_LIFETIME=.*/SESSION_LIFETIME=480/' .env || echo 'SESSION_LIFETIME=480' >> .env
  grep -q '^SESSION_COOKIE=' .env && sed -i 's/^SESSION_COOKIE=.*/SESSION_COOKIE=viberide_session/' .env || echo 'SESSION_COOKIE=viberide_session' >> .env
  grep -q '^SESSION_SAME_SITE=' .env && sed -i 's/^SESSION_SAME_SITE=.*/SESSION_SAME_SITE=lax/' .env || echo 'SESSION_SAME_SITE=lax' >> .env
  # Host-only cookie (empty SESSION_DOMAIN breaks browsers; omit key entirely)
  sed -i '/^SESSION_DOMAIN=/d' .env
  # Auto secure flag (null) — works for HTTP now and HTTPS later behind nginx
  sed -i '/^SESSION_SECURE_COOKIE=/d' .env
}

echo "==> PHP $(php -r 'echo PHP_VERSION;')"
echo "==> APP_ENV=$(read_env APP_ENV) NODE_ENV=$(read_env NODE_ENV)"

if [ ! -f .env ]; then
  if [ -f deploy/production.env ]; then
    cp deploy/production.env .env
  else
    echo "ERROR: .env missing"
    exit 1
  fi
fi

# Force production mode on server (never run as local/development here)
grep -q '^APP_ENV=' .env && sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env || echo 'APP_ENV=production' >> .env
grep -q '^NODE_ENV=' .env && sed -i 's/^NODE_ENV=.*/NODE_ENV=production/' .env || echo 'NODE_ENV=production' >> .env
grep -q '^APP_DEBUG=' .env && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env || echo 'APP_DEBUG=false' >> .env

ensure_session_env

if [ "$(read_env DB_HOST)" = "mysql" ]; then
  sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
fi

DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"
DB_ROOT_PASSWORD="$(read_env DB_ROOT_PASSWORD)"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-$DB_PASSWORD}"

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ]; then
  echo "ERROR: Missing DB_* in .env"
  exit 1
fi

export DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD

echo "==> MySQL (Docker)"
sudo systemctl stop mysql 2>/dev/null || true
if docker compose version >/dev/null 2>&1; then
  DC="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  DC="docker-compose"
else
  echo "ERROR: docker compose required"; exit 1
fi
$DC -f docker-compose.db.yml up -d

for i in $(seq 1 30); do
  if docker exec viverider-mysql mysqladmin ping -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent >/dev/null 2>&1; then
    echo "MySQL ready"; break
  fi
  [ "$i" -eq 30 ] && { docker logs viverider-mysql --tail 50 || true; exit 1; }
  sleep 2
done

docker exec viverider-mysql mysql -h 127.0.0.1 -u root -p"${DB_ROOT_PASSWORD}" -e \
  "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'; FLUSH PRIVILEGES;" \
  2>/dev/null || true

SEED_LOCK="/var/www/.viverider-db-seeded"
if [ ! -f "${SEED_LOCK}" ] && [ -f database/database.sql ]; then
  HAS_TABLE="$(docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='landing_homes';" 2>/dev/null || echo 0)"
  ROWS=0
  if [ "${HAS_TABLE}" = "1" ]; then
    ROWS="$(docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
      "SELECT COUNT(*) FROM ${DB_DATABASE}.landing_homes;" 2>/dev/null || echo 0)"
  fi
  if [ "${ROWS}" = "0" ]; then
    echo "==> Seeding database..."
    docker exec viverider-mysql mysql -h 127.0.0.1 -u root -p"${DB_ROOT_PASSWORD}" -e \
      "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%'; FLUSH PRIVILEGES;"
    sed -e "s/\`taxi1\`/\`${DB_DATABASE}\`/g" -e '/^CREATE DATABASE IF NOT EXISTS/d' -e '/^USE `/d' \
      database/database.sql | docker exec -i viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"
  fi
  sudo touch "${SEED_LOCK}"
fi

echo "==> Storage"
sudo mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public/uploads bootstrap/cache
if [ -d /var/www/.viverider-persist/uploads ]; then
  sudo rsync -a /var/www/.viverider-persist/uploads/ storage/app/public/uploads/
fi
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
sudo rm -f public/storage
sudo ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"
sudo chown -h www-data:www-data public/storage

sudo -u www-data php artisan package:discover --ansi

echo "==> Migrate (includes sessions table)"
sudo -u www-data php artisan migrate --force --no-interaction
sudo -u www-data php artisan migrate:status || true

# Guarantee sessions table exists even if migration history is odd
SESSIONS_OK="$(docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -Nse \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_name='sessions';" 2>/dev/null || echo 0)"
if [ "${SESSIONS_OK}" != "1" ]; then
  echo "==> Creating sessions table..."
  sudo -u www-data php artisan session:table 2>/dev/null || true
  sudo -u www-data php artisan migrate --force --no-interaction || \
  docker exec viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e \
    "CREATE TABLE IF NOT EXISTS sessions (
      id varchar(191) NOT NULL PRIMARY KEY,
      user_id bigint unsigned NULL,
      ip_address varchar(45) NULL,
      user_agent text NULL,
      payload longtext NOT NULL,
      last_activity int NOT NULL,
      KEY sessions_user_id_index (user_id),
      KEY sessions_last_activity_index (last_activity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
fi

if [ -f deploy/rebrand-viberide.sql ]; then
  docker exec -i viverider-mysql mysql -h 127.0.0.1 -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" \
    < deploy/rebrand-viberide.sql || true
fi

echo "==> Cache (nginx left untouched — your SSL/IP/domain config stays)"
sudo -u www-data php artisan config:clear || true
sudo -u www-data php artisan cache:clear || true
sudo -u www-data php artisan view:clear || true
sudo -u www-data php artisan route:clear || true
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.5-fpm 2>/dev/null || sudo systemctl reload php-fpm 2>/dev/null || true

echo "Deploy complete | APP_ENV=production | SESSION_DRIVER=database | migrate=ok | nginx=unchanged"
