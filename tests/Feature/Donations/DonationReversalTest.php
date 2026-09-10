<?php

use App\Models\Donation;
use Illuminate\Database\QueryException;

/**
 * donations_amount_sign CHECK (create_donations_table): положительный
 * донат ИЛИ отрицательное сторно, третьего не дано. Исправление ошибки —
 * новая строка, не UPDATE.
 */
it('records a correction as a negative reversal row, never as an update', function () {
    $donation = Donation::factory()->create(['amount_minor' => 3_000_00]);

    $reversal = Donation::create([
        'donor_id' => $donation->donor_id,
        'amount_minor' => -3_000_00,
        'currency' => 'KGS',
        'fund_type' => 'general',
        'status' => 'reversed',
        'reversal_of_id' => $donation->id,
        'paid_at' => now(),
    ]);

    expect($reversal->amount_minor)->toBe(-3_000_00);
});

it('rejects a positive amount on a reversal row', function () {
    $donation = Donation::factory()->create(['amount_minor' => 3_000_00]);

    expect(fn () => Donation::create([
        'donor_id' => $donation->donor_id,
        'amount_minor' => 3_000_00, // must be negative when reversal_of_id is set
        'currency' => 'KGS',
        'fund_type' => 'general',
        'status' => 'reversed',
        'reversal_of_id' => $donation->id,
        'paid_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('is append-only for money fields: amount_minor cannot be updated', function () {
    $donation = Donation::factory()->create(['amount_minor' => 3_000_00]);

    expect(fn () => $donation->update(['amount_minor' => 5_000_00]))
        ->toThrow(RuntimeException::class);
});

/**
 * pending -> completed по вебхуку провайдера (FinikWebhookController)
 * требует UPDATE неденежных полей — то же самое разрешает и БД-триггер
 * forbid_donation_money_update (см. миграцию). Money-guard в
 * IsAppendOnly должен целиться в конкретные поля, а не блокировать любой
 * UPDATE целиком, иначе подтверждение оплаты в принципе невозможно.
 */
it('allows updating non-money fields like status and paid_at (needed for the provider webhook)', function () {
    $donation = Donation::factory()->create(['status' => 'pending', 'paid_at' => null]);

    $donation->update(['status' => 'completed', 'paid_at' => now()]);

    expect($donation->fresh()->status)->toBe('completed');
    expect($donation->fresh()->paid_at)->not->toBeNull();
});

it('still rejects amount_minor even when bundled with an allowed field in the same update', function () {
    $donation = Donation::factory()->create(['amount_minor' => 3_000_00, 'status' => 'pending']);

    expect(fn () => $donation->update(['status' => 'completed', 'amount_minor' => 5_000_00]))
        ->toThrow(RuntimeException::class);
});
