<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Beneficiary;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\FundCase;
use Illuminate\Database\Seeder;

/**
 * НЕ часть стандартного db:seed — только для демо-окружений
 * (`php artisan db:seed --class=DemoSeeder`). Реальный деплой фонда не
 * должен получать выдуманные кейсы автоматически.
 *
 * Данные бенефициаров тут вымышленные, не реальные истории — специально,
 * чтобы это демо можно было безопасно держать на любом хостинге, не
 * только на сервере в КР (см. ARCHITECTURE.md §10, §12: требование
 * держать реальные данные бенефициаров в КР касается реальных людей, не
 * тестовых записей).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Идемпотентность: контейнер на демо-хостинге может перезапуститься
        // (передеплой, краш) — не плодим кейсы заново при каждом старте.
        if (FundCase::query()->exists()) {
            return;
        }

        $cases = [
            [
                'category' => 'medical',
                'title' => ['ky' => 'Айгүлгө дарылоо үчүн жардам', 'ru' => 'Помощь на лечение для Айгуль'],
                'story' => [
                    'ky' => '8 жаштагы Айгүлгө жүрөк операциясы керек. Үй-бүлөсү каражат таба албай жатат.',
                    'ru' => '8-летней Айгуль нужна операция на сердце. Семья не может собрать сумму самостоятельно.',
                ],
                'budget' => 250_000_00,
                'allocated' => 87_000_00,
                'allows_zakat' => true,
            ],
            [
                'category' => 'winter_food',
                'title' => ['ky' => 'Ош облусундагы 6 үй-бүлөгө кышкы азык-түлүк', 'ru' => 'Зимние продуктовые наборы для 6 семей в Ошской области'],
                'story' => [
                    'ky' => 'Алыскы айылдарда жашаган көп балалуу үй-бүлөлөргө кыш мезгилине азык-түлүк керек.',
                    'ru' => 'Многодетным семьям в отдалённых сёлах нужны продуктовые наборы на зиму.',
                ],
                'budget' => 120_000_00,
                'allocated' => 120_000_00,
                'allows_zakat' => false,
                'status' => 'closed',
            ],
            [
                'category' => 'medical',
                'title' => ['ky' => 'Бакыттын протезине жардам', 'ru' => 'Протез для Бакыта'],
                'story' => [
                    'ky' => 'Жол кырсыгынан кийин Бакытка заманбап протез керек.',
                    'ru' => 'После аварии Бакыту нужен современный протез ноги.',
                ],
                'budget' => 400_000_00,
                'allocated' => 15_000_00,
                'allows_zakat' => true,
            ],
            [
                'category' => 'winter_food',
                'title' => ['ky' => 'Бишкектеги жалгыз бой карыяларга жардам', 'ru' => 'Помощь одиноким пожилым людям в Бишкеке'],
                'story' => [
                    'ky' => '12 жалгыз бой карыяга ай сайын азык-түлүк топтому.',
                    'ru' => 'Ежемесячный продуктовый набор для 12 одиноких пожилых людей.',
                ],
                'budget' => 60_000_00,
                'allocated' => 42_000_00,
                'allows_zakat' => false,
            ],
        ];

        foreach ($cases as $data) {
            $beneficiary = Beneficiary::factory()->create();

            $case = FundCase::create([
                'beneficiary_id' => $beneficiary->id,
                'category' => $data['category'],
                'status' => $data['status'] ?? 'active',
                'public_title' => $data['title'],
                'public_story' => $data['story'],
                'currency' => 'KGS',
                'budget_minor' => $data['budget'],
                'allows_zakat' => $data['allows_zakat'],
            ]);

            if ($data['allocated'] > 0) {
                $donor = Donor::factory()->create();

                $donation = Donation::create([
                    'donor_id' => $donor->id,
                    'amount_minor' => $data['allocated'],
                    'currency' => 'KGS',
                    'fund_type' => 'general',
                    'status' => 'completed',
                    'provider' => 'fake',
                    'provider_ref' => (string) \Illuminate\Support\Str::uuid(),
                    'paid_at' => now()->subDays(random_int(1, 20)),
                ]);

                Allocation::create([
                    'donation_id' => $donation->id,
                    'case_id' => $case->id,
                    'amount_minor' => $data['allocated'],
                ]);
            }
        }
    }
}
