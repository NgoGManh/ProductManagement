# =========================
# Stage 1: Composer vendor
# =========================
FROM composer:2 AS vendor
WORKDIR /var/www/html

# Cài vendor trước (chưa chạy scripts vì chưa có artisan)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy source vào để tối ưu autoload (không chạy artisan ở stage này)
COPY . .
RUN composer dump-autoload --classmap-authoritative

# =========================
# Stage 2: Build front assets (Vite)
# =========================
FROM node:20-alpine AS assets
WORKDIR /app

# Cài deps FE
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm i; fi

# Copy code FE và cấu hình build
COPY resources ./resources
COPY vite.config.* tailwind.config.* postcss.config.* ./

# Build Vite -> public/build
RUN npm run build

# =========================
# Stage 3: PHP-FPM runtime
# =========================
FROM php:8.4-fpm-alpine

# Cài extensions cần thiết
RUN apk add --no-cache \
      icu-dev libzip-dev oniguruma-dev \
      libpng-dev libjpeg-turbo-dev freetype-dev bash \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) pdo_mysql intl gd zip opcache

# (Tuỳ chọn) Redis extension nếu bạn dùng REDIS_CLIENT=phpredis
# RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html

# (Tuỳ chọn) Tạo cấu hình PHP cơ bản
RUN { \
  echo "memory_limit=512M"; \
  echo "upload_max_filesize=32M"; \
  echo "post_max_size=32M"; \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.validate_timestamps=0"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/app.ini

# Copy toàn bộ source code
COPY . .

# Copy vendor & assets đã build từ các stage trước
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public
# (Nếu bạn build SSR hay có files FE khác cần giữ, copy thêm tại đây)

# Quyền thư mục cho Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Healthcheck nhẹ (tuỳ chọn)
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
  CMD php -v >/dev/null || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
