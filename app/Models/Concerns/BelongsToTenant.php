<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Удобство и производительность, не граница безопасности — та проходит
 * через RLS-политики (см. миграцию setup_row_level_security). Если этот
 * scope забыт в каком-то запросе, RLS всё равно не даст утечки между
 * тенантами; это разделение осознанное и описано в ARCHITECTURE.md §4.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable().'.tenant_id', TenantContext::current());
        });

        static::creating(function (Model $model) {
            if (! $model->tenant_id) {
                $model->tenant_id = TenantContext::current();
            }
        });
    }
}
