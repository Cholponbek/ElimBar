<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => '+996'.fake()->unique()->numerify('#########'),
            'city' => fake()->randomElement(['Бишкек', 'Ош']),
        ];
    }
}
