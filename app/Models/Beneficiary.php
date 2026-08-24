<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Полностью приватная сущность — недоступна роли app_public на уровне
 * Postgres (не только скоупом, см. RLS-миграцию). Не подключать эту
 * модель ни в один контроллер контура A.
 */
class Beneficiary extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['full_name', 'phone', 'document_number', 'city', 'notes'];

    public function requests(): HasMany
    {
        return $this->hasMany(CaseRequest::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(FundCase::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }
}
