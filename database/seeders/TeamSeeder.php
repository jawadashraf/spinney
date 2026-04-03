<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

final class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('is_system_admin', true)->first()
            ?? User::where('email', 'manuk.minasyan1@gmail.com')->first()
            ?? User::first();

        if (! $owner) {
            $this->command->error('No user found to own the teams. Please run SystemAdministratorSeeder or LocalSeeder first.');

            return;
        }

        $teams = [
            'Liaison',
            'Counselor',
            'Management',
        ];

        foreach ($teams as $name) {
            Team::firstOrCreate(
                ['name' => $name],
                [
                    'user_id' => $owner->id,
                    'personal_team' => false,
                ]
            );
        }
    }
}
