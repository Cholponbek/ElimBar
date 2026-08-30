<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Донор. Не User — вход по телефону через OTP, без пароля (см. §OTP в
 * ARCHITECTURE.md и Domain\Donors\Services\OtpLoginService).
 */
class Donor extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['phone', 'name', 'email', 'locale', 'phone_verified_at', 'show_name_publicly'];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'show_name_publicly' => 'boolean',
    ];

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
