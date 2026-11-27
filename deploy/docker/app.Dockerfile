# Image chạy Laravel (PHP-FPM + Nginx). Build context: thư mục gốc repo.
#   docker build -f deploy/docker/app.Dockerfile -t ngoi-app .

# 1) Thư viện PHP
FROM composer:2 AS vendor
WORKDIR /app/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# 2) Build CSS/JS (Tailwind + Vite). Giao diện nằm ở frontend/
FROM node:22-alpine AS assets
WORKDIR /app/backend
COPY backend/package.json backend/package-lock.json ./
RUN npm ci
COPY backend/vite.config.js ./
COPY backend/app/Enums ./app/Enums
COPY --from=vendor /app/backend/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY frontend /app/frontend
RUN npm run build

# 3) Image chạy
FROM serversideup/php:8.3-fpm-nginx
ENV APP_BASE_DIR=/var/www/html/backend \
    NGINX_WEBROOT=/var/www/html/backend/public \
    PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    TZ=Asia/Ho_Chi_Minh
USER root
RUN install-php-extensions intl bcmath
USER www-data
WORKDIR /var/www/html
COPY --chown=www-data:www-data data ./data
COPY --chown=www-data:www-data frontend ./frontend
COPY --chown=www-data:www-data backend ./backend
COPY --chown=www-data:www-data --from=vendor /app/backend/vendor ./backend/vendor
COPY --chown=www-data:www-data --from=assets /app/backend/public/build ./backend/public/build
WORKDIR /var/www/html/backend
# Gộp cache config/route/view/event để khởi động nhanh (chạy lại lúc container start qua AUTORUN)
RUN php artisan package:discover --ansi
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD curl -fsS http://localhost:8080/api/health || exit 1
