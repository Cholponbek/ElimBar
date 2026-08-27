# Демо-деплой (Railway/Render), НЕ прод-образ. Прод — docker-compose.yml
# в корне репозитория (php-fpm + Caddy + отдельные Postgres/Redis/MinIO).
# Здесь всё в одном контейнере через `php artisan serve`, без Redis —
# специально просто и дёшево для показа донору, а не для реальной нагрузки.
FROM php:8.3-cli-alpine

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        nodejs \
        npm \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pgsql bcmath zip intl opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules

RUN chmod +x docker/railway/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker/railway/entrypoint.sh"]
