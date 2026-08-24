<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Proof extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['disk', 'path', 'sha256', 'original_name', 'mime', 'size_bytes', 'uploaded_by'];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Подписанная ссылка с коротким TTL — приватный бакет, публичного доступа нет. */
    public function temporaryUrl(int $minutes = 10): string
    {
        return Storage::disk($this->disk)
            ->temporaryUrl($this->path, now()->addMinutes($minutes));
    }
}
