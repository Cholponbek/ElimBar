<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Второй, defense-in-depth слой поверх БД-триггеров/правил, которые уже
 * запрещают UPDATE/DELETE на append-only таблицах (donations, allocations,
 * disbursements). Модель отказывает раньше, чем уйдёт запрос — БД остаётся
 * источником истины, если этот trait когда-нибудь забудут навесить на
 * новую append-only модель.
 */
trait IsAppendOnly
{
    protected static function bootIsAppendOnly(): void
    {
        static::updating(function (Model $model) {
            throw new RuntimeException(static::class.' is append-only: no UPDATE, insert a reversal row instead.');
        });

        static::deleting(function (Model $model) {
            throw new RuntimeException(static::class.' is append-only: no DELETE, insert a reversal row instead.');
        });
    }
}
