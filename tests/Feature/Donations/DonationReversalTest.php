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

it('is append-only: donations cannot be updated', function () {
    $donation = Donation::factory()->create(['amount_minor' => 3_000_00]);

    expect(fn () => $donation->update(['amount_minor' => 5_000_00]))
        ->toThrow(RuntimeException::class);
});
