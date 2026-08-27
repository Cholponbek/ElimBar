<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Роль app_public — та же, что использует DonationController. Не
 * тестируем здесь полный донор-флоу (donor -> donation -> allocation)
 * через Pest: pgsql_public — отдельная физическая сессия Postgres, а
 * RefreshDatabase оборачивает в транзакцию только дефолтное подключение,
 * поэтому созданный в тесте FundCase не виден со стороны pgsql_public до
 * коммита — это ограничение тестового стенда, не баг приложения. Полный
 * путь донора проверен вручную: напрямую через psql от имени app_public
 * (donor -> donation -> allocation, agregates пересчитались) и через
 * реальный запрос в браузере на DonationController::store() — см.
 * PR "Fake donation flow".
 */
it('never lets app_public write to disbursements, even with a fabricated case_id', function () {
    // Право проверяется раньше FK — не нужен реально существующий case_id,
    // чтобы убедиться, что до проверки бюджета дело не доходит вообще.
    expect(fn () => DB::connection('pgsql_public')->table('disbursements')->insert([
        'tenant_id' => 1,
        'case_id' => 999999,
        'proof_id' => 999999,
        'amount_minor' => 1_000_00,
        'currency' => 'KGS',
        'disbursed_by' => 999999,
        'disbursed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class, 'permission denied for table disbursements');
});
