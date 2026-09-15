<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Синглтон на tenant (UNIQUE(tenant_id), см. миграцию) — "О нас"/
 * "Контакты" на главной странице, редактируется в Filament
 * (App\Filament\Pages\SiteSettingsPage). Строка всегда существует
 * (создаётся прямо в миграции, не сидером — entrypoint.sh не запускает
 * db:seed при деплое), current() поэтому не нуждается в firstOrCreate().
 */
class SiteSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'about_title', 'about_body', 'contact_address',
        'contact_phone', 'contact_email',
        'contact_instagram', 'contact_facebook', 'contact_whatsapp',
    ];

    protected $casts = [
        'about_title' => 'array',
        'about_body' => 'array',
        'contact_address' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
