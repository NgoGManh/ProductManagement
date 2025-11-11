#!/bin/sh
set -e

echo "🚀 Starting Laravel Application Setup..."

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "📦 Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/database

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache config
echo "⚙️  Caching configuration..."
php artisan config:clear || true
php artisan config:cache

# Clear and cache routes
echo "🛣️  Caching routes..."
php artisan route:clear || true
php artisan route:cache

# Clear and cache views
echo "👁️  Caching views..."
php artisan view:clear || true
php artisan view:cache

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force || true

# Seed database if DB_SEED is set to true
if [ "$DB_SEED" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force || true
fi

echo "✅ Application setup completed!"

# Execute the main command
exec "$@"
