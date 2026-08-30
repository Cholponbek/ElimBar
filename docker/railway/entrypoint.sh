#!/bin/sh
set -e

# Ждём, пока управляемый Postgres реально примет подключение — на
# Railway/Render контейнер приложения обычно стартует раньше, чем БД
# полностью готова принимать соединения.
echo "Waiting for database..."
until php artisan db:show >/dev/null 2>&1; do
  sleep 2
done
echo "Database is up."

# Skipped at build time (--no-scripts on composer dump-autoload) since
# any artisan bootstrap opens a real DB connection here — our own
# TenantServiceProvider fires on ConnectionEstablished and immediately
# runs a query to set the RLS session var, which fails against the
# nonexistent build-time database. Safe now that Postgres is confirmed
# reachable above.
php artisan package:discover --ansi

php artisan migrate --force

# Диск 'public' (фото кейсов) отдаётся через public/storage — это
# симлинк, который раньше нигде не создавался. Идемпотентно: если
# симлинк уже есть, команда просто пропускает шаг.
php artisan storage:link

# Оба сидера идемпотентны (см. комментарии в самих классах) — безопасно
# гонять на каждом старте контейнера, включая передеплои и краш-рестарты.
php artisan db:seed --force
php artisan db:seed --class=DemoSeeder --force

php artisan config:cache
php artisan route:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
