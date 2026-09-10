<?php

use App\Support\TenantContext;
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

/**
 * Регрессия: donors — единственная таблица здесь, у которой app_public
 * получает не только SELECT/INSERT, но и точечный UPDATE (на locale,
 * name, show_name_publicly, updated_at — донор обновляет их же при
 * повторном донате с того же номера, DonationController::store() делает
 * updateOrCreate()). Postgres проверяет привилегию по КАЖДОЙ колонке в
 * SET, поэтому если забыть хоть одну — падает весь UPDATE с "permission
 * denied", даже если остальные разрешены. Ровно так это и сломалось на
 * DO: миграция show_name_publicly гранула name/show_name_publicly, но
 * не locale и не updated_at (последнюю Eloquent трогает сам, неявно, на
 * любом update()/save()) — упало только при повторном донате с уже
 * существующим номером (ветка UPDATE в updateOrCreate), не при первом
 * (там INSERT, туда эта проблема не долетает).
 *
 * tenants(id=1) — не в транзакции RefreshDatabase, засеян прямо в
 * миграции create_tenants_table, поэтому виден из pgsql_public без
 * обхода того же ограничения, что описано в комментарии над файлом.
 */
it('lets app_public update an existing donor on a repeat donation (locale/name/show_name_publicly)', function () {
    TenantContext::set(1, DB::connection('pgsql_public'));

    $phone = '+996700'.random_int(100000, 999999);

    $donorId = DB::connection('pgsql_public')->table('donors')->insertGetId([
        'tenant_id' => 1,
        'phone' => $phone,
        'locale' => 'ky',
        'show_name_publicly' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        DB::connection('pgsql_public')->table('donors')->where('id', $donorId)->update([
            'locale' => 'ru',
            'name' => 'Тест',
            'show_name_publicly' => true,
            'updated_at' => now(),
        ]);

        $donor = DB::connection('pgsql_public')->table('donors')->find($donorId);
        expect($donor->locale)->toBe('ru');
        expect($donor->name)->toBe('Тест');
        expect((bool) $donor->show_name_publicly)->toBeTrue();
    } finally {
        // app_public не имеет DELETE на donors (и не должен) — чистим
        // владельческим дефолтным подключением, не откатывается
        // транзакцией RefreshDatabase (см. комментарий над файлом).
        DB::table('donors')->where('id', $donorId)->delete();
    }
});
