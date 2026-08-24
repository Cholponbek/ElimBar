<?php

use App\Models\Disbursement;
use App\Models\FundCase;
use App\Models\Proof;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * cases_disbursed_within_budget CHECK (create_cases_table) + триггер
 * apply_disbursement (create_disbursements_table). proof_id NOT NULL —
 * disbursements_proof_id_foreign — выплата без документа невозможна на
 * уровне схемы, не только в форме.
 */
it('disburses within the case budget and updates the running total', function () {
    $case = FundCase::factory()->create(['budget_minor' => 50_000_00]);
    $proof = Proof::factory()->create();
    $staff = User::factory()->create();

    Disbursement::create([
        'case_id' => $case->id,
        'proof_id' => $proof->id,
        'amount_minor' => 20_000_00,
        'currency' => 'KGS',
        'disbursed_by' => $staff->id,
        'disbursed_at' => now(),
    ]);

    expect($case->fresh()->disbursed_minor)->toBe(20_000_00);
});

it('rejects a disbursement that exceeds the case budget', function () {
    $case = FundCase::factory()->create(['budget_minor' => 10_000_00]);
    $proof = Proof::factory()->create();
    $staff = User::factory()->create();

    expect(fn () => Disbursement::create([
        'case_id' => $case->id,
        'proof_id' => $proof->id,
        'amount_minor' => 10_000_01,
        'currency' => 'KGS',
        'disbursed_by' => $staff->id,
        'disbursed_at' => now(),
    ]))->toThrow(QueryException::class);

    expect($case->fresh()->disbursed_minor)->toBe(0);
});

it('rejects a disbursement without a proof document', function () {
    $case = FundCase::factory()->create(['budget_minor' => 10_000_00]);
    $staff = User::factory()->create();

    expect(fn () => Disbursement::create([
        'case_id' => $case->id,
        'proof_id' => null,
        'amount_minor' => 5_000_00,
        'currency' => 'KGS',
        'disbursed_by' => $staff->id,
        'disbursed_at' => now(),
    ]))->toThrow(QueryException::class);
});
