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

php artisan migrate --force

# Оба сидера идемпотентны (см. комментарии в самих классах) — безопасно
# гонять на каждом старте контейнера, включая передеплои и краш-рестарты.
php artisan db:seed --force
php artisan db:seed --class=DemoSeeder --force

php artisan config:cache
php artisan route:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
