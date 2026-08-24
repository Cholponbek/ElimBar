<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Собственный рекуррент (провайдер обычно не даёт готовых подписок — только
 * токенизацию карты). Horizon-джоба читает next_charge_at, ретраи 1/3/7
 * дней управляются retry_stage (см. Domain\Subscriptions).
 *
 * payment_token_ref — ссылка на токен у провайдера, НИКОГДА не данные карты.
 */
class Subscription extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'donor_id', 'case_id', 'campaign_id', 'amount_minor', 'currency',
        'frequency', 'provider', 'payment_token_ref', 'status',
        'next_charge_at', 'retry_stage', 'cancelled_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'retry_stage' => 'integer',
        'next_charge_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $hidden = ['payment_token_ref'];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(FundCase::class, 'case_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(SubscriptionCharge::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
