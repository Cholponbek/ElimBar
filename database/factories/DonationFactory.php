<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'donor_id' => Donor::factory(),
            'amount_minor' => fake()->numberBetween(100_00, 5_000_00), // 100–5000 сом
            'currency' => 'KGS',
            'fund_type' => 'general',
            'status' => 'completed',
            'provider' => 'test',
            'provider_ref' => (string) fake()->uuid(),
            'paid_at' => now(),
        ];
    }

    public function zakat(): static
    {
        return $this->state(fn () => ['fund_type' => 'zakat']);
    }
}
