#!/bin/sh
set -e

cd /var/www/html

echo "==> Viveride container starting..."

# Wait for MySQL
if [ "${WAIT_FOR_DB}" != "false" ]; then
    echo "==> Waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    php /var/www/html/docker/php/wait-for-db.php
fi

# Generate APP_KEY if missing
if [ -z "${APP_KEY}" ] || [ "${APP_KEY}" = "" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# Ensure storage symlink exists
php artisan storage:link --force 2>/dev/null || true

# Run migrations on app container only (not queue/scheduler)
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "==> Running migrations..."
    php artisan migrate --force
fi

# Cache config for production
if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# Fix permissions for mounted volumes
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "==> Ready. Executing: $*"
exec "$@"
