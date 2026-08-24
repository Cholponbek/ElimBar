<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Идемпотентность вебхуков: UNIQUE(provider, external_event_id) на уровне
 * схемы. См. Domain\Payments\Services\WebhookGuard::record() — вставка
 * сначала, обработка payload только если вставка реально произошла.
 */
class PaymentEvent extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['provider', 'external_event_id', 'payload', 'received_at', 'processed_at'];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
