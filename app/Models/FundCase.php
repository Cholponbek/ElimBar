<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/**
 * Таблица cases. Названа FundCase, не Case — `case` зарезервировано языком
 * (enum-выражения с PHP 8.1+).
 *
 * budget_minor управляется сервисом (бюджет утверждается вручную),
 * allocated_minor/disbursed_minor — только триггерами БД из allocations/
 * disbursements (см. миграции create_allocations_table /
 * create_disbursements_table). Прямая запись сюда — ошибка, guard ниже её
 * ловит на уровне модели ещё до похода в БД.
 */
class FundCase extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'request_id', 'campaign_id', 'beneficiary_id', 'category', 'status',
        'public_title', 'public_story', 'public_photo_id', 'currency',
        'budget_minor', 'allows_zakat', 'closed_at',
    ];

    protected $casts = [
        'public_title' => 'array',
        'public_story' => 'array',
        'budget_minor' => 'integer',
        'allocated_minor' => 'integer',
        'disbursed_minor' => 'integer',
        'allows_zakat' => 'boolean',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (FundCase $case) {
            if ($case->isDirty('allocated_minor') || $case->isDirty('disbursed_minor')) {
                throw new RuntimeException(
                    'cases.allocated_minor / disbursed_minor are trigger-managed '.
                    '(see allocations/disbursements triggers) — insert the underlying '.
                    'Allocation/Disbursement row instead of writing these fields directly.'
                );
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'request_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function publicPhoto(): BelongsTo
    {
        return $this->belongsTo(Proof::class, 'public_photo_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'case_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class, 'case_id');
    }
}
