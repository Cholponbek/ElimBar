<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проставляет app.tenant_id для текущего подключения к Postgres — это то,
 * что читает RLS-политика tenant_isolation на каждой доменной таблице.
 *
 * Фаза 1: единственный тенант, id фиксирован конфигом (config('tenant.id')).
 * Фаза 3 (мультифонд): здесь появится резолвинг по домену/поддомену — сама
 * модель данных и RLS-политики уже готовы к этому, меняется только эта
 * middleware.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Фаза 1: единственный тенант из конфига. Фаза 3 (мультифонд):
        // резолвинг по домену/поддомену меняется здесь — TenantContext,
        // модель данных и RLS-политики уже готовы к этому без изменений.
        TenantContext::set((int) config('tenant.id'));

        return $next($request);
    }
}
