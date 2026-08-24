<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['title', 'description', 'status'];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(FundCase::class);
    }
}
