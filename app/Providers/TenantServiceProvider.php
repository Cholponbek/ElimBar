<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\ServiceProvider;

/**
 * Гарантирует app.tenant_id на каждом физическом подключении к Postgres —
 * не только в HTTP-запросе (SetTenantContext middleware), но и в
 * Horizon-воркерах и artisan-командах, где middleware не выполняется, а
 * подключение живёт дольше одного запроса.
 */
class TenantServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['events']->listen(ConnectionEstablished::class, function (): void {
            TenantContext::set((int) config('tenant.id'));
        });
    }
}
