ARG PHP_VERSION=8.4
ARG NODE_VERSION=20

# ---------- Stage 1: node builder ----------
FROM node:${NODE_VERSION}-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.* tsconfig.json postcss.config.* tailwind.config.* ./
RUN npm run build

# ---------- Stage 2: composer deps ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache COMPOSER_CACHE_DIR=/tmp/cache composer install --no-dev --prefer-dist --no-interaction --no-progress

# ---------- Stage 3: php runtime ----------
FROM php:${PHP_VERSION}-fpm-alpine AS app
WORKDIR /var/www/html

# System deps
RUN apk add --no-cache bash git curl zip unzip \
  libpng libpng-dev libjpeg-turbo-dev libwebp-dev \
  oniguruma-dev icu-data-full icu-dev libzip-dev libxml2-dev \
  sqlite-libs sqlite-dev \
  shadow supervisor

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
  && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_sqlite mbstring intl zip xml

# Opcache
RUN docker-php-ext-install opcache
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Create user (www-data uid/gid 1000) for local dev compatibility
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Copy app
COPY . /var/www/html

# Copy vendor from stage
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copy built assets
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Permissions
RUN mkdir -p storage bootstrap/cache \
  && chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R ug+rw storage bootstrap/cache

# Supervisor for queue/scheduler (optional, used in worker image)
COPY docker/supervisord.conf /etc/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data
EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Stage 4: worker image (queue + schedule) ----------
FROM app AS worker
USER root
ENTRYPOINT ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
CMD []


