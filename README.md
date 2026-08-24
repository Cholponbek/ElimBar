# ElimBar

Фандрайзинговая платформа ОБФ «Элим, барсыңбы?!» (Бишкек, КР). Laravel 12,
Inertia + Vue 3 (контур донора) + Filament (контур фонда) на одной
PostgreSQL-модели данных с RLS-изоляцией. Полный проектный контекст,
инварианты и открытые вопросы — в [ARCHITECTURE.md](ARCHITECTURE.md).

## Стек

Laravel 12 · PHP 8.3+ · Filament 3 · Inertia + Vue 3 + Tailwind ·
PostgreSQL 16 · Redis + Horizon · MinIO · Caddy · Pest · Larastan (уровень 6+)

## Быстрый старт (Docker)

```bash
cp .env.example .env
# заполнить DB_PASSWORD, DB_STAFF_PASSWORD, DB_PUBLIC_PASSWORD,
# MINIO_ROOT_PASSWORD в .env

docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install && docker compose exec app npm run build
```

Три роли PostgreSQL (`app_owner`, `app_staff`, `app_public`) и
RLS-политики создаются миграцией `2025_01_01_000900_setup_row_level_security`
— отдельного шага для них не требуется, только пароли в `.env`
(`DB_STAFF_PASSWORD`, `DB_PUBLIC_PASSWORD`) до первого `migrate`.

## Тесты

```bash
createdb elimbar_testing   # один раз, локальные креды — см. .env.testing
php artisan test
```

Тесты идут против настоящего PostgreSQL, не sqlite — денежные инварианты
живут в триггерах/CHECK/RLS, а не в PHP (см. ARCHITECTURE.md §11).

## Статический анализ

```bash
composer install
vendor/bin/phpstan analyse   # уровень 6+, конфиг в phpstan.neon
vendor/bin/pint --test       # code style
```

> **Известное ограничение среды разработки этой сессии:** в песочнице, где
> собирался этот каркас, исходящий доступ к GitHub API был ограничен рамками
> сессии, из-за чего `phpstan/phpstan` (только этот пакет — у него нет
> git-источника, только zip-дистрибутив через GitHub API) не докачался. Сам
> `composer.json`/`composer.lock` корректны; на обычной машине или в CI с
> обычным сетевым доступом `composer install` отработает без каких-либо
> дополнительных действий.

## Структура

- `ARCHITECTURE.md` — контуры, модель данных, RLS, платежи, открытые вопросы.
- `app/Domain/**` — бизнес-логика по доменам (сервисы, контракты, DTO).
- `app/Models` — Eloquent-модели: тенант-скоуп (`BelongsToTenant`),
  append-only защита (`IsAppendOnly`) поверх БД-триггеров.
- `database/migrations` — схема, CHECK-констрейнты, триггеры, RLS-политики.
  Денежные инварианты закреплены здесь, не в коде приложения.
- `docker/` — `Dockerfile` (php-fpm), `Caddyfile`.
- `tests/Feature/{Donations,Disbursements,Payments}` — тесты на денежные
  инварианты (пара happy path / нарушение) и идемпотентность вебхуков.

## Открытые вопросы фазы 0

Платёжный провайдер, юридическая конструкция доната, трансграничная
передача (Cloudflare/TLS), иностранное финансирование (закон КР №72),
синхронизация Бишкек — Ош. Детали — в ARCHITECTURE.md §12.
