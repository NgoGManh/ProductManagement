#!/usr/bin/env bash
set -e

cd /var/www/html

# Check if vendor exists, if not, install dependencies
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
  echo "Vendor directory not found, installing dependencies..."
  composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts || true
  # Run scripts after vendor is installed
  composer dump-autoload --optimize || true
fi

if [ ! -f ".env" ]; then
  cp .env.example .env || true
fi

# Copy public/build from image backup to mounted volume if it doesn't exist
# This ensures built assets are available even when public is mounted from host
# Note: /var/www/html/public/build is overwritten by mount, so we use backup location
BUILD_SOURCE="/app_build/public/build"
if [ -d "$BUILD_SOURCE" ] && [ ! -z "$(ls -A $BUILD_SOURCE 2>/dev/null)" ]; then
  # Check if mounted public/build is empty or missing
  if [ ! -d "./public/build" ] || [ -z "$(ls -A ./public/build 2>/dev/null)" ]; then
    echo "Copying built assets from image to public/build..."
    mkdir -p ./public/build
    cp -r $BUILD_SOURCE/* ./public/build/ 2>/dev/null || true
    echo "Built assets copied successfully"
  else
    echo "public/build already exists, skipping copy"
  fi
fi

# Wait for database to be ready (for MySQL)
if [ "${DB_CONNECTION}" = "mysql" ]; then
  echo "Waiting for database..."
  until php artisan db:show --quiet 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
  done
  echo "Database is up!"
fi

php artisan key:generate --force || true
php artisan storage:link || true
php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"


