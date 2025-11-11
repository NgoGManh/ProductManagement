#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f ".env" ]; then
  cp .env.example .env || true
fi

php artisan key:generate --force || true
php artisan storage:link || true
php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"


