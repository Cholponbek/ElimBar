<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\IsAppendOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only. INSERT триггером увеличивает donations.allocated_minor и
 * cases.allocated_minor (см. миграцию create_allocations_table) — не
 * трогать эти суммы из PHP, только вставлять строки сюда.
 */
class Allocation extends Model
{
    use BelongsToTenant, HasFactory, IsAppendOnly;

    protected $fillable = ['donation_id', 'case_id', 'amount_minor', 'created_by'];

    protected $casts = [
        'amount_minor' => 'integer',
    ];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(FundCase::class, 'case_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
