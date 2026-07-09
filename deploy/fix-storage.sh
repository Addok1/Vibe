#!/usr/bin/env bash
set -euo pipefail

# Fix missing images after deploy (storage symlink + nginx /storage alias).
# Run on server: sudo bash /var/www/viverider/deploy/fix-storage.sh

APP_DIR="${APP_DIR:-/var/www/viverider}"
cd "${APP_DIR}"

echo "==> Ensuring upload directories exist..."
sudo mkdir -p storage/app/public/uploads
sudo chown -R www-data:www-data storage
sudo chmod -R ug+rwx storage

echo "==> Creating public/storage symlink..."
sudo rm -f public/storage
sudo ln -sfn "${APP_DIR}/storage/app/public" "${APP_DIR}/public/storage"
sudo chown -h www-data:www-data public/storage

if [ -f deploy/nginx-viverider.conf ]; then
  echo "==> Updating nginx config..."
  sudo cp deploy/nginx-viverider.conf /etc/nginx/sites-available/viberidegh.online
  sudo ln -sf /etc/nginx/sites-available/viberidegh.online /etc/nginx/sites-enabled/viberidegh.online
  sudo rm -f /etc/nginx/sites-enabled/default
  sudo nginx -t
  sudo systemctl reload nginx
fi

echo "==> Verifying sample images..."
for f in \
  storage/app/public/uploads/website/images/hero3.png \
  storage/app/public/uploads/system-admin/logo/rest.png \
  storage/app/public/uploads/system-admin/logo/workspace.jpg
do
  if [ -f "$f" ]; then
    echo "  OK  $f"
  else
    echo "  MISSING  $f"
  fi
done

FILE_COUNT="$(find storage/app/public/uploads -type f 2>/dev/null | wc -l | tr -d ' ')"
echo ""
echo "Total upload files: ${FILE_COUNT}"
if [ "${FILE_COUNT}" -lt 10 ]; then
  echo "WARNING: Very few upload files. Re-deploy from git or restore uploads backup."
fi

echo ""
echo "Test URL: http://viberidegh.online/storage/uploads/website/images/hero3.png"
