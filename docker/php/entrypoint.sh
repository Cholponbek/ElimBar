#!/bin/sh
set -e

# app-контейнер стартует до того, как Postgres реально готов принимать
# соединения (healthcheck в compose ждёт только сам процесс postgres,
# не гарантирует момент между "запустился" и "готов к первому connect").
echo "Waiting for database..."
until php artisan db:show >/dev/null 2>&1; do
  sleep 2
done
echo "Database is up."

php artisan package:discover --ansi
php artisan migrate --force
php artisan config:cache
php artisan route:cache

exec php-fpm
