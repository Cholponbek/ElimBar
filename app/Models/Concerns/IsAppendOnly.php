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
    /**
     * Денежные/identity-поля, UPDATE которых запрещён — по умолчанию
     * пусто, что блокирует ЛЮБОЙ UPDATE целиком (Allocation, Disbursement
     * — там легитимного UPDATE не бывает вообще). Donation переопределяет
     * этот метод, зеркаля БД-триггер forbid_donation_money_update: тот
     * уже разрешает менять неденежные поля (status/paid_at/provider_ref —
     * нужно для перехода pending -> completed по вебхуку), этот trait не
     * должен быть строже базы, для которой он defense-in-depth.
     *
     * Метод, не свойство: PHP не даёт классу и трейту, из которого он
     * составлен, объявлять одно и то же свойство с разными значениями по
     * умолчанию (fatal "incompatible" на любой composition) — методы
     * трейт/класс переопределяют штатно.
     */
    protected static function appendOnlyFields(): array
    {
        return [];
    }

    protected static function bootIsAppendOnly(): void
    {
        static::updating(function (Model $model) {
            $guarded = static::appendOnlyFields();

            if ($guarded === []) {
                throw new RuntimeException(static::class.' is append-only: no UPDATE, insert a reversal row instead.');
            }

            $touched = array_intersect($guarded, array_keys($model->getDirty()));
            if ($touched !== []) {
                throw new RuntimeException(static::class.' append-only fields cannot be updated ('.implode(', ', $touched).'): insert a reversal row instead.');
            }
        });

        static::deleting(function (Model $model) {
            throw new RuntimeException(static::class.' is append-only: no DELETE, insert a reversal row instead.');
        });
    }
}
