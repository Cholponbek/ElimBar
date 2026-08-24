<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Append-only (см. IsAppendOnly + БД-триггер forbid_donation_money_update).
 * Исправление — новая строка с reversal_of_id и отрицательной amount_minor.
 *
 * amount_minor / allocated_minor — bigint, минорные единицы (тыйын). Никогда
 * float. currency — ISO 4217, по умолчанию KGS.
 */
class Donation extends Model
{
    use BelongsToTenant, HasFactory, IsAppendOnly;

    protected $fillable = [
        'donor_id', 'campaign_id', 'case_id', 'subscription_id',
        'amount_minor', 'currency', 'provider_fee_minor', 'fund_type',
        'status', 'provider', 'provider_ref', 'paid_at', 'reversal_of_id',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'provider_fee_minor' => 'integer',
        'allocated_minor' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(FundCase::class, 'case_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function remainingMinor(): int
    {
        return $this->amount_minor - $this->allocated_minor;
    }
}
