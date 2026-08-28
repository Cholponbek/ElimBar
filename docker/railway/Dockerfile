# Демо-деплой (Railway/Render), НЕ прод-образ. Прод — docker-compose.yml
# в корне репозитория (php-fpm + Caddy + отдельные Postgres/Redis/MinIO).
# Здесь всё в одном контейнере через `php artisan serve`, без Redis —
# специально просто и дёшево для показа донору, а не для реальной нагрузки.
FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        nodejs \
        npm \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath zip intl opcache pcntl \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# .dockerignore excludes these runtime cache dirs (to avoid baking in
# stale local artifacts), which means they never land in the build
# context at all — not even as empty directories. Laravel's view
# config does `realpath(storage_path('framework/views'))`, which
# silently returns false for a path that doesn't exist, and Blade's
# Compiler throws "Please provide a valid cache path" the moment
# `composer dump-autoload`'s package:discover hook boots the app.
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache

# --no-scripts: composer's post-autoload-dump hook runs `artisan
# package:discover`, which boots the app far enough that our own
# TenantServiceProvider (fires on ConnectionEstablished) tries a real
# DB query — there is no database at build time. Run package:discover
# instead in entrypoint.sh, once the real Postgres is confirmed up.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && npm run build \
    && rm -rf node_modules

RUN chmod +x docker/railway/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker/railway/entrypoint.sh"]
