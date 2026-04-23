<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@magichotels.ma'],
            [
                'name'     => 'Admin Magic Hotels',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_ADMIN,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@magichotels.ma'],
            [
                'name'     => 'Staff Magic Hotels',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_STAFF,
            ]
        );

        $this->command->info('✓ Utilisateurs admin créés.');
        $this->command->line('  admin@magichotels.ma / password');
        $this->command->line('  staff@magichotels.ma / password');
    }
}
