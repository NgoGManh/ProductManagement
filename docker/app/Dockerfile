# =========================================
# Stage 1: PHP base with extensions & Composer
# =========================================
FROM php:8.4-fpm AS php-base

# Cài deps hệ thống + wkhtmltopdf
RUN apt-get update && apt-get install -y \
    git curl wget unzip zip nano bash \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libxml2-dev libzip-dev libicu-dev \
    xfonts-75dpi xfonts-base fontconfig \
    libxrender1 libxext6 libx11-6 \
    && rm -rf /var/lib/apt/lists/*

# Cài wkhtmltopdf 0.12.6.1 (Debian 12 compatible)
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-1/wkhtmltox_0.12.6.1-1.bookworm_amd64.deb \
    -O /tmp/wkhtml.deb \
    && apt-get update \
    && apt-get install -y /tmp/wkhtml.deb \
    && rm /tmp/wkhtml.deb

# Cấu hình extensions PHP bắt buộc cho Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo_mysql intl gd zip opcache xml

# Cài Composer chính thức
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# =========================================
# Stage 2: Install vendor (composer install)
# =========================================
FROM php-base AS vendor

WORKDIR /var/www/html
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy source tạm để dump autoload
COPY . .
RUN composer dump-autoload --classmap-authoritative


# =========================================
# Stage 3: Build Frontend (Vite)
# =========================================
FROM node:20-alpine AS assets
WORKDIR /app

# Cài frontend deps
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm i; fi

# Copy resources và config build
COPY resources ./resources
COPY vite.config.* tailwind.config.* postcss.config.* ./

# Build ra public/build
RUN npm run build


# =========================================
# Stage 4: Final runtime (PHP-FPM + Laravel)
# =========================================
FROM php-base AS runtime

WORKDIR /var/www/html

# Copy toàn bộ source code
COPY . .

# Copy vendor và assets
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public ./public

# Quyền thư mục Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# PHP config
RUN { \
  echo "memory_limit=512M"; \
  echo "upload_max_filesize=32M"; \
  echo "post_max_size=32M"; \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.validate_timestamps=0"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/app.ini

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
  CMD php -v >/dev/null || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
