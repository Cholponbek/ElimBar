<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only. proof_id NOT NULL на уровне схемы — выплату без документа
 * невозможно создать, это FK-констрейнт, не проверка в форме Filament.
 */
class Disbursement extends Model
{
    use BelongsToTenant, HasFactory, IsAppendOnly;

    protected $fillable = [
        'case_id', 'proof_id', 'amount_minor', 'currency', 'recipient_note',
        'disbursed_by', 'disbursed_at', 'reversal_of_id',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'disbursed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(FundCase::class, 'case_id');
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(Proof::class);
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
