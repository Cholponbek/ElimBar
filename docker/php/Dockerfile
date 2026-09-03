# Стадия сборки фронтенда отдельная — node/npm не нужны в конечном
# php-fpm образе, только собранный public/build (тот же .dockerignore,
# что и раньше, исключает public/build из основного контекста, поэтому
# без этой стадии он в контейнер вообще не попадал).
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath zip intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN composer dump-autoload --optimize

# .dockerignore исключает эти каталоги из контекста сборки (чтобы не
# запекать протухшие локальные логи/кэш), а значит их нет в образе даже
# как пустых директорий — Laravel падает на первом же обращении
# (сессии, скомпилированные view, логи). www-data — пользователь, под
# которым реально работают php-fpm воркеры.
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["php-fpm"]
