<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Правит cases_public из 2025_01_01_000900_setup_row_level_security.
     *
     * WITH (security_invoker = true) на этом view был ошибкой: с ним
     * Postgres проверяет права ЗАПРАШИВАЮЩЕЙ роли (app_public) не только
     * на сам view, но и на таблицу cases под ним — а у app_public на
     * cases нет вообще никаких прав (REVOKE ALL, см. ту же миграцию).
     * Результат: "permission denied for table cases" на любой запрос
     * через cases_public, обнаружено при первом реальном использовании
     * витрины донора (contour A).
     *
     * Без security_invoker view выполняется с правами владельца (роль
     * миграций) — это штатный и безопасный способ дать ограниченной роли
     * доступ к проекции приватной таблицы, при условии что сама защита
     * (какие строки/колонки видны) не зависит от прав invoker'а. Здесь
     * она и не зависит: список колонок фиксирован в SELECT, а фильтр по
     * tenant/статусу — явный WHERE с current_setting('app.tenant_id'),
     * который читает переменную СЕССИИ (проставляется TenantContext на
     * подключении app_public), а не привилегии роли. Так что уровень
     * защиты не меняется, только способ его достижения.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_id,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);

        DB::statement('GRANT SELECT ON cases_public TO app_public');
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW cases_public
            WITH (security_invoker = true) AS
            SELECT
                id, tenant_id, campaign_id, category, status,
                public_title, public_story, public_photo_id,
                currency, budget_minor, allocated_minor, disbursed_minor,
                allows_zakat, closed_at, created_at, updated_at
            FROM cases
            WHERE status <> 'draft'
              AND tenant_id = current_setting('app.tenant_id', true)::bigint;
        SQL);
    }
};
