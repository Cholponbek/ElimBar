<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\FundCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundCase>
 */
class FundCaseFactory extends Factory
{
    protected $model = FundCase::class;

    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'category' => fake()->randomElement(['medical', 'winter_food']),
            'status' => 'active',
            'public_title' => ['ky' => fake()->sentence(3), 'ru' => fake()->sentence(3)],
            'currency' => 'KGS',
            'budget_minor' => 100_000_00, // 100 000 сом
            'allows_zakat' => false,
        ];
    }

    public function allowsZakat(): static
    {
        return $this->state(fn () => ['allows_zakat' => true]);
    }
}
