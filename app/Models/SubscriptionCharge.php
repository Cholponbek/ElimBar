<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionCharge extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['subscription_id', 'donation_id', 'attempted_at', 'status', 'retry_stage', 'failure_reason'];

    protected $casts = [
        'attempted_at' => 'datetime',
        'retry_stage' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
