# =========================================
# Stage 1: PHP base with extensions & Composer
# =========================================
FROM php:8.4-fpm-alpine AS php-base

# Cài đặt extensions Laravel cần (bao gồm XML cho Excel)
RUN apk add --no-cache \
    icu-dev libzip-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev bash git curl zip unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql intl gd zip opcache xml \
 && docker-php-ext-enable opcache xml

# Cài Composer chính thức vào image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# =========================================
# Stage 2: Cài vendor PHP
# =========================================
FROM php-base AS vendor

WORKDIR /var/www/html
COPY composer.json composer.lock ./

# Cài vendor (không chạy artisan scripts ở đây)
RUN composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy source tạm để dump autoload (không ảnh hưởng đến final build)
COPY . .
RUN composer dump-autoload --classmap-authoritative

# =========================================
# Stage 3: Build Frontend (Vite)
# =========================================
FROM node:20-alpine AS assets
WORKDIR /app

# Cài deps frontend
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm i; fi

# Copy resources và configs build
COPY resources ./resources
COPY vite.config.* tailwind.config.* postcss.config.* ./

# Build ra public/build
RUN npm run build

# =========================================
# Stage 4: Final runtime (PHP-FPM + Laravel)
# =========================================
FROM php-base AS runtime

WORKDIR /var/www/html

# Copy toàn bộ source code Laravel
COPY . .

# Copy vendor và build assets từ các stage trước
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public

# Thiết lập quyền cho storage & cache
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Thiết lập cấu hình PHP
RUN { \
  echo "memory_limit=512M"; \
  echo "upload_max_filesize=32M"; \
  echo "post_max_size=32M"; \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.validate_timestamps=0"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/app.ini

# Healthcheck (tuỳ chọn)
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
  CMD php -v >/dev/null || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
