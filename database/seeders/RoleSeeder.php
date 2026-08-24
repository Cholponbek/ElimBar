<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Роли контура B из MVP scope: оператор, верификатор, финансист, админ.
 * Права (permissions) добавляются по мере роста Filament Resources — не
 * заводим их впрок здесь.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['operator', 'verifier', 'financier', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
