#!/usr/bin/env bash
set -e

cd /var/www/html

# Fix permissions for bootstrap/cache and storage (run as root)
# This is critical - bootstrap/cache must be writable for Laravel package manifest
mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data bootstrap/cache storage
chmod -R 775 bootstrap/cache storage

# Check if vendor exists, if not, install dependencies
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
  echo "Vendor directory not found, installing dependencies..."
  composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts || true
  composer dump-autoload --optimize || true
fi

if [ ! -f ".env" ]; then
  cp .env.example .env || true
fi

# Wait for database to be ready (for MySQL)
if [ "${DB_CONNECTION}" = "mysql" ]; then
  echo "Waiting for database..."
  until gosu www-data php artisan db:show --quiet 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
  done
  echo "Database is up!"
fi

# Start supervisord (runs as root, but programs run as www-data)
exec /usr/bin/supervisord -c /etc/supervisord.conf
