# Multi-stage build for Laravel + React application
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install Node dependencies (including dev dependencies for build)
RUN npm ci

# Copy source files needed for build
COPY vite.config.js tailwind.config.js postcss.config.js tsconfig.json components.json ./
COPY resources ./resources
COPY public ./public

# Build frontend assets
RUN npm run build

# PHP 8.4 FPM image
FROM php:8.4-cli-alpine

# Install system dependencies (build dependencies)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    pkgconf \
    sqlite-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    jpeg-dev \
    libjpeg-turbo-dev \
    linux-headers

# Install runtime dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    sqlite \
    libpng \
    libzip \
    oniguruma \
    freetype \
    jpeg \
    libjpeg-turbo

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Clean up build dependencies
RUN apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev dependencies for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy application files
COPY . .

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create SQLite database directory
RUN mkdir -p /var/www/html/database \
    && touch /var/www/html/database/database.sqlite \
    && chmod 664 /var/www/html/database/database.sqlite

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/database

# Expose port 8000
EXPOSE 8000

# Use entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]

# Default command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
