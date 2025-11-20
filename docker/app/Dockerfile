# =========================================
# Stage 1: PHP base + extensions + wkhtmltopdf
# =========================================
FROM php:8.4-fpm AS php-base

# Cài đặt system packages + PHP extensions + wkhtmltopdf
RUN apt-get update && apt-get install -y \
    git curl wget unzip zip nano \
    fontconfig \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libicu-dev libzip-dev libonig-dev libxml2-dev \
    wkhtmltopdf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql intl gd zip opcache \
 && rm -rf /var/lib/apt/lists/*

# (Tuỳ chọn) Kiểm tra xem wkhtmltopdf có trong container chưa
RUN wkhtmltopdf --version || echo "wkhtmltopdf not found in PATH?"

# Cài composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# =========================================
# Stage 2: Cài vendor PHP (composer install)
# =========================================
FROM php-base AS vendor

WORKDIR /var/www/html
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy code vào để tạo autoload chuẩn
COPY . .
RUN composer dump-autoload --classmap-authoritative

# =========================================
# Stage 3: Build Frontend (Vite)
# =========================================
FROM node:20-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./

RUN if [ -f package-lock.json ]; then \
      npm ci --no-audit --no-fund; \
    else \
      npm i --no-audit --no-fund; \
    fi

COPY resources ./resources
COPY vite.config.* tailwind.config.* postcss.config.* tsconfig.* ./

RUN npm run build

# =========================================
# Stage 4: Final runtime (PHP-FPM + Laravel)
# =========================================
FROM php-base AS runtime

WORKDIR /var/www/html

# Copy toàn bộ source
COPY . .

# Copy vendor & assets từ các stage trước
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public

# Quyền thư mục Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# (Tuỳ chọn) cấu hình PHP thêm
RUN { \
  echo "memory_limit=512M"; \
  echo "upload_max_filesize=32M"; \
  echo "post_max_size=32M"; \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.validate_timestamps=0"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/app.ini

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
  CMD php -v > /dev/null || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
