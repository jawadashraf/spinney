<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class SystemAdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@spinneyhill.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
                'is_system_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
