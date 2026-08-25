<?php

use App\Models\Allocation;
use App\Models\Donation;
use App\Models\FundCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * cases_allocated_within_bounds / donations_allocated_within_amount CHECK
 * (см. миграции create_cases_table / create_donations_table) плюс триггер
 * apply_allocation (create_allocations_table). Каждый инвариант проверяется
 * парой: happy path и попытка его нарушить.
 */
it('allocates a donation to a case within its remaining amount', function () {
    $donation = Donation::factory()->create(['amount_minor' => 10_000_00]);
    $case = FundCase::factory()->create(['budget_minor' => 50_000_00]);

    Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 6_000_00,
    ]);

    expect($donation->fresh()->allocated_minor)->toBe(6_000_00);
    expect($case->fresh()->allocated_minor)->toBe(6_000_00);
});

it('rejects an allocation that exceeds the donation amount', function () {
    $donation = Donation::factory()->create(['amount_minor' => 10_000_00]);
    $case = FundCase::factory()->create(['budget_minor' => 50_000_00]);

    Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 4_000_00,
    ]);

    // В savepoint: Postgres помечает всю транзакцию как aborted после любой
    // ошибки внутри неё, даже пойманной в PHP. DB::transaction() здесь
    // вкладывается в транзакцию RefreshDatabase как SAVEPOINT, поэтому
    // после ожидаемого исключения тест может продолжать делать запросы.
    expect(fn () => DB::transaction(fn () => Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 7_000_00, // 4 000 + 7 000 > 10 000
    ])))->toThrow(QueryException::class);

    expect($donation->fresh()->allocated_minor)
        ->toBe(4_000_00, 'the rejected allocation must not partially apply');
});

it('is append-only: allocations cannot be updated', function () {
    $donation = Donation::factory()->create(['amount_minor' => 10_000_00]);
    $case = FundCase::factory()->create(['budget_minor' => 50_000_00]);

    $allocation = Allocation::create([
        'donation_id' => $donation->id,
        'case_id' => $case->id,
        'amount_minor' => 1_000_00,
    ]);

    expect(fn () => $allocation->update(['amount_minor' => 2_000_00]))
        ->toThrow(RuntimeException::class);
});
