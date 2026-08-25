<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Проекция FundCase для контура A (донор). Единственная модель, которую
 * можно использовать в публичных контроллерах/страницах — она читает
 * через connection pgsql_public (роль app_public) view cases_public,
 * а не таблицу cases напрямую.
 *
 * Роль app_public физически не имеет прав ни на cases, ни на
 * beneficiaries (см. миграцию setup_row_level_security и ARCHITECTURE.md
 * §5) — попытка сделать здесь ->with('beneficiary') или добавить
 * beneficiary_id в этот класс упадёт с "permission denied", а не тихо
 * утечёт приватные данные.
 *
 * Read-only: сохранять/обновлять кейсы отсюда нельзя и не нужно —
 * это делает контур B через FundCase.
 */
class PublicCase extends Model
{
    use BelongsToTenant;

    protected $connection = 'pgsql_public';

    protected $table = 'cases_public';

    public $timestamps = false;

    protected $casts = [
        'public_title' => 'array',
        'public_story' => 'array',
        'budget_minor' => 'integer',
        'allocated_minor' => 'integer',
        'disbursed_minor' => 'integer',
        'allows_zakat' => 'boolean',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
