<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Единственный писатель app.tenant_id для сессии Postgres. RLS-политика
 * tenant_isolation читает эту переменную на каждой доменной таблице —
 * если она не установлена, current_setting(..., true) вернёт NULL,
 * tenant_id = NULL никогда не true, и запрос молча получит ноль строк.
 * Поэтому tenant.id проставляется не только в HTTP middleware, но и на
 * каждое физическое подключение к БД (см. TenantServiceProvider) — это
 * покрывает HTTP, Horizon-джобы и artisan-команды одним и тем же путём.
 */
class TenantContext
{
    public static function set(int $tenantId): void
    {
        app()->instance('tenant.id', $tenantId);

        // set_config() is Postgres-only. Guarded (not just skipped on
        // driver mismatch) because this also runs from a ConnectionEstablished
        // listener during artisan bootstrap (package:discover, tinker, tests
        // against a non-pgsql connection) — it must never break console
        // commands that don't actually touch tenant-scoped tables.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("SELECT set_config('app.tenant_id', ?, false)", [(string) $tenantId]);
    }

    public static function current(): int
    {
        return app()->bound('tenant.id') ? app('tenant.id') : (int) config('tenant.id');
    }
}
