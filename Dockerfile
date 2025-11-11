FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.* ./
RUN npm run build

FROM serversideup/php:8.4-fpm-nginx
WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public ./public

USER root
RUN mkdir -p storage/logs && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

USER www-data

RUN composer install --no-dev --optimize-autoloader
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 8080
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]