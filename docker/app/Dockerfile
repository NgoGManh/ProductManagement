# -------- Stage 1: Composer deps
FROM composer:2 AS vendor
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy toàn bộ source để dump autoload chính xác theo nguồn
COPY . .
RUN composer dump-autoload --optimize

# -------- Stage 2: Build front assets (Vite)
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
# ưu tiên npm ci nếu có lock
RUN if [ -f package-lock.json ]; then npm ci; else npm i; fi
COPY resources ./resources
COPY vite.config.* tailwind.config.* postcss.config.* ./
RUN npm run build

# -------- Stage 3: PHP-FPM runtime
FROM php:8.3-fpm-alpine

# extensions: pdo_mysql, intl, gd, zip, opcache
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev bash \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql intl gd zip opcache

# Configure PHP
COPY docker/app/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# Copy source
COPY . .

# Copy vendor & build
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/resources ./resources
COPY --from=assets /app/public ./public

# Quyền
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Health check optional
HEALTHCHECK --interval=30s --timeout=3s CMD php -v || exit 1

# php-fpm lắng trên 9000
EXPOSE 9000
CMD ["php-fpm", "-F"]
