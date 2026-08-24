<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Таблица requests. Названа CaseRequest, не Request — конфликт с
 * Illuminate\Http\Request. Три статуса: pending -> verified -> rejected.
 * Одобрение создаёт FundCase отдельным действием сервиса, не статусом.
 */
class CaseRequest extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'beneficiary_id', 'submitted_by', 'verified_by', 'category', 'status',
        'description', 'requested_amount_minor', 'currency', 'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_amount_minor' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function case(): HasOne
    {
        return $this->hasOne(FundCase::class, 'request_id');
    }
}
