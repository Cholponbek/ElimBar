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

# storage:link идемпотентна (сама пропускает шаг, если ссылка уже стоит) —
# но раньше нигде не вызывалась в принципе, только вручную один раз в
# контейнере. `docker compose build` каждый раз создаёт контейнер заново,
# так что ручная ссылка терялась при пересборке — фото кейсов переставали
# открываться (public/storage no such file or directory), хотя сами файлы
# были на диске целые. Теперь ссылка восстанавливается на каждом старте.
php artisan storage:link

php artisan config:cache
php artisan route:cache

exec php-fpm
