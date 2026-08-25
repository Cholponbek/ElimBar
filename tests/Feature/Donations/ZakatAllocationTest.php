<?php

use App\Models\Allocation;
use App\Models\Donation;
use App\Models\FundCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Религиозное ограничение, живёт в БД: триггер enforce_zakat_allocation
 * (create_allocations_table), не проверка в форме.
 */
it('allocates a zakat donation to a case that accepts zakat', function () {
    $donation = Donation::factory()->zakat()->create(['amount_minor' => 5_000_00]);
    $case = FundCase::factory()->allowsZakat()->create(['budget_minor' => 50_000_00]);

    Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 5_000_00,
    ]);

    expect($donation->fresh()->allocated_minor)->toBe(5_000_00);
});

it('rejects a zakat donation allocated to a case that does not accept zakat', function () {
    $donation = Donation::factory()->zakat()->create(['amount_minor' => 5_000_00]);
    $case = FundCase::factory()->create(['budget_minor' => 50_000_00, 'allows_zakat' => false]);

    // Savepoint, не голая транзакция: иначе Postgres помечает всю
    // транзакцию теста как aborted и следующая проверка ниже сама упадёт.
    expect(fn () => DB::transaction(fn () => Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 5_000_00,
    ])))->toThrow(QueryException::class);

    expect($donation->fresh()->allocated_minor)->toBe(0);
});
