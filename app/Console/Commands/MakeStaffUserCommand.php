<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Filament's own make:filament-user creates a User row but never assigns
 * a role — and User::canAccessPanel() requires one (see app/Models/User.php).
 * Without a role, login fails with the exact same "invalid credentials"
 * message as a wrong password (Filament doesn't distinguish, by design, to
 * avoid leaking whether an account exists) — indistinguishable from a typo
 * without digging into canAccessPanel() itself. This command creates the
 * user and assigns a role in the same step so that trap can't happen.
 */
class MakeStaffUserCommand extends Command
{
    protected $signature = 'make:staff-user';

    protected $description = 'Create a Filament staff user with a role assigned (avoids the "user exists but canAccessPanel() is false" login trap)';

    public function handle(): int
    {
        $roles = Role::pluck('name')->all();

        if ($roles === []) {
            $this->error('No roles found — run `php artisan db:seed --class=RoleSeeder` first.');

            return self::FAILURE;
        }

        $name = $this->ask('Name');
        $email = $this->ask('Email address');
        $password = $this->secret('Password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => ['required', 'string'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = $this->choice('Role', $roles, count($roles) - 1);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->assignRole($role);

        $this->info("Created {$email} with role '{$role}'.");

        return self::SUCCESS;
    }
}
