<?php

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donor>
 */
class DonorFactory extends Factory
{
    protected $model = Donor::class;

    public function definition(): array
    {
        return [
            'phone' => '+996'.fake()->unique()->numerify('#########'),
            'name' => fake()->name(),
            'locale' => fake()->randomElement(['ky', 'ru']),
        ];
    }
}
