# =========================================
# Stage 1: PHP base + extensions + wkhtmltopdf
# =========================================
FROM php:8.4-fpm AS php-base

# Cài hệ thống + PHP extensions + wkhtmltopdf (Debian)
RUN apt-get update && apt-get install -y \
      git curl wget unzip zip nano \
      fontconfig \
      libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
      libicu-dev libzip-dev libonig-dev libxml2-dev \
  && wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb \
        -O /tmp/wkhtml.deb \
  && apt-get install -y /tmp/wkhtml.deb \
  && rm /tmp/wkhtml.deb \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql intl gd zip opcache \
  && rm -rf /var/lib/apt/lists/*

# Composer chính thức
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# =========================================
# Stage 2: Cài vendor PHP
# =========================================
FROM php-base AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy code để dump autoload cho chuẩn
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
COPY vite.config.* tailwind.config.* postcss.config.* ./

RUN npm run build

# =========================================
# Stage 4: Runtime (PHP-FPM chạy Laravel)
# =========================================
FROM php-base AS runtime

WORKDIR /var/www/html

# Copy toàn bộ source
COPY . .

# Copy vendor & public/build từ các stage trước
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public

# Quyền cho storage & cache
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && mkdir -p storage/app/public/pdfs storage/app/public/exports \
 && chown -R www-data:www-data storage/app/public \
 && chmod -R 775 storage/app/public

# Config PHP
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
  CMD php -v >/dev/null || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
