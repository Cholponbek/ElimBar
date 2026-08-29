<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Заявка прямо с сайта, до проверки сотрудником. Не Beneficiary/CaseRequest
 * — app_public не имеет прав на них (ARCHITECTURE.md §5). Сотрудник либо
 * конвертирует (создаёт Beneficiary + CaseRequest, сохраняет
 * converted_request_id), либо отклоняет (rejection_reason).
 */
class PublicIntake extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'full_name', 'phone', 'category', 'description',
        'requested_amount_minor', 'currency', 'status',
        'converted_request_id', 'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'requested_amount_minor' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function convertedRequest(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'converted_request_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
