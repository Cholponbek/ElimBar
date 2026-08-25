<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Намеренно без WithoutModelEvents: tenant_id проставляется в
     * App\Models\Concerns\BelongsToTenant через Eloquent-событие
     * `creating`. Этот трейт его отключает — сидер упадёт на NOT NULL
     * constraint, что и произошло при первом прогоне (см. ARCHITECTURE.md
     * §4). Это правильное поведение: лучше падать здесь, чем молча писать
     * NULL в tenant_id.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('admin');
    }
}
