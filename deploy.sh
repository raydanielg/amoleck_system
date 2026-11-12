#!/usr/bin/env bash
# Production deploy — Amoleck clinic system
# Usage: bash deploy.sh   (run from repo root, as the deploy user)
set -euo pipefail
cd "$(dirname "$0")"

echo "==> Pulling latest code"
git pull --ff-only

echo "==> Installing PHP dependencies (no dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing frontend deps and building assets"
npm ci --omit=dev
npm run build

echo "==> Running migrations"
php artisan migrate --force

echo "==> Ensuring storage link and permissions"
php artisan storage:link 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache

echo "==> Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

echo "==> Restarting queue workers"
php artisan queue:restart 2>/dev/null || true

echo "==> Done. Verify: php artisan about"
