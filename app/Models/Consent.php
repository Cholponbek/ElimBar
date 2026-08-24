<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Версионированное согласие бенефициара на раскрытие данных, не булево
 * поле — document_version фиксирует, под каким именно текстом согласия
 * была дана подпись.
 */
class Consent extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['beneficiary_id', 'document_version', 'scope', 'granted_at', 'granted_by'];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
