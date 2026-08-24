<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Граница безопасности, а не Eloquent-скоуп. Приложение подключается не
     * владельцем таблиц (владелец = DB_USERNAME из миграций, обычно суперюзер
     * при первом деплое), а одной из двух ролей ниже, обе с RLS.
     *
     * app_staff  — контур B (Filament). Видит все домены своего tenant_id,
     *              включая beneficiaries и приватные колонки cases.
     * app_public — контур A (публичная витрина/отчёт). Не имеет вообще
     *              никаких прав на beneficiaries и на базовую таблицу cases;
     *              читает только через VIEW cases_public с уже урезанными
     *              колонками. Если контроллер контура A случайно потянет
     *              ->with('beneficiary') — Postgres вернёт "permission
     *              denied for table beneficiaries", а не ФИО и диагноз.
     *
     * Логин-роли и пароли берутся из .env (DB_STAFF_*, DB_PUBLIC_*) — см.
     * docker-compose.yml и config/database.php (connections 'pgsql' и
     * 'pgsql_public').
     */
    private array $tenantScopedTables = [
        'users', 'donors', 'otp_codes', 'beneficiaries', 'campaigns', 'requests',
        'cases', 'proofs', 'donations', 'allocations', 'disbursements',
        'subscriptions', 'subscription_charges', 'payment_events',
        'consents', 'access_logs',
    ];

    private array $staffOnlyTables = [
        'beneficiaries', 'requests', 'proofs', 'consents', 'access_logs',
        'otp_codes', 'subscription_charges', 'payment_events',
        // cases: app_public reads only the cases_public view (below), never
        // the base table — it carries beneficiary-linked private columns.
        'cases',
    ];

    public function up(): void
    {
        $staffUser = env('DB_STAFF_USERNAME', 'elimbar_staff');
        $staffPassword = str_replace("'", "''", env('DB_STAFF_PASSWORD', 'change-me'));
        $publicUser = env('DB_PUBLIC_USERNAME', 'elimbar_public');
        $publicPassword = str_replace("'", "''", env('DB_PUBLIC_PASSWORD', 'change-me'));

        DB::unprepared(<<<SQL
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'app_staff') THEN
                    CREATE ROLE app_staff NOLOGIN;
                END IF;
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'app_public') THEN
                    CREATE ROLE app_public NOLOGIN;
                END IF;

                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '{$staffUser}') THEN
                    CREATE ROLE {$staffUser} LOGIN PASSWORD '{$staffPassword}';
                END IF;
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '{$publicUser}') THEN
                    CREATE ROLE {$publicUser} LOGIN PASSWORD '{$publicPassword}';
                END IF;

                GRANT app_staff TO {$staffUser};
                GRANT app_public TO {$publicUser};
            END
            \$\$;
        SQL);

        foreach ($this->tenantScopedTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$table}
                    USING (tenant_id = current_setting('app.tenant_id', true)::bigint)
                    WITH CHECK (tenant_id = current_setting('app.tenant_id', true)::bigint)
            SQL);

            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON {$table} TO app_staff");
            DB::statement("GRANT USAGE, SELECT ON SEQUENCE {$table}_id_seq TO app_staff");

            if (! in_array($table, $this->staffOnlyTables, true)) {
                // app_public получает право читать/писать таблицы контура A
                // (donations/allocations идут через доменные сервисы, не
                // напрямую из контроллера, но роль всё равно ограничена
                // тенантом через RLS-политику выше).
                DB::statement("GRANT SELECT, INSERT ON {$table} TO app_public");
                DB::statement("GRANT USAGE, SELECT ON SEQUENCE {$table}_id_seq TO app_public");
            }
        }

        // Публичная проекция cases: без beneficiary_id, без приватных полей.
        //
        // security_invoker=true (PG15+) — обязательно. Без него view
        // выполняется с правами владельца (обычно суперюзер миграций),
        // а суперюзер по умолчанию BYPASSRLS — то есть RLS-политика
        // tenant_isolation на cases молча не применится и view отдаст
        // кейсы всех тенантов. С security_invoker=true политика
        // проверяется под ролью, которая реально сделала SELECT
        // (app_public), плюс WHERE tenant_id=... ниже — второй, явный
        // слой на случай, если это когда-нибудь изменится.
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

        DB::statement('GRANT SELECT ON cases_public TO app_public');
        // Defensive redundancy: staffOnlyTables above already skips granting
        // app_public anything on these, this just makes the boundary explicit.
        DB::statement('REVOKE ALL ON cases FROM app_public');
        DB::statement('REVOKE ALL ON beneficiaries FROM app_public');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS cases_public');

        foreach ($this->tenantScopedTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
