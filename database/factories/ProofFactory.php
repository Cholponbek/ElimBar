<?php

namespace Database\Factories;

use App\Models\Proof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proof>
 */
class ProofFactory extends Factory
{
    protected $model = Proof::class;

    public function definition(): array
    {
        return [
            'disk' => 'proofs',
            'path' => 'proofs/'.fake()->uuid().'.pdf',
            'sha256' => hash('sha256', fake()->uuid()),
            'original_name' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 500_000),
        ];
    }
}
