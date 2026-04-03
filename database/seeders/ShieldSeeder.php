<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class ShieldSeeder extends Seeder
{
    /**
     * Seed the Shield roles, create a test user for each role,
     * and assign super_admin to system administrators.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdminRole = Role::firstOrCreate(
            ['name' => Utils::getSuperAdminName()],
            ['guard_name' => 'web'],
        );

        // Application roles from the Phase 1 Handoff Permissions Matrix.
        // Maps role name => matching team name for team assignment.
        $applicationRoles = [
            'liaison' => 'Liaison',
            //            'assessor' => 'Assessment',
            //            'drug_alcohol' => 'Drug & Alcohol',
            //            'spiritual' => 'Spiritual Counselling',
            //            'education_outreach' => 'Education & Outreach',
            //            'aftercare' => 'Aftercare',
            //            'safeguarding' => 'Safeguarding',
            //            'fundraising' => 'Fundraising',
            'counselor' => 'Counselor',
            'manager' => 'Management',
            'admin' => 'Management',
        ];

        foreach ($applicationRoles as $roleName => $teamName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web'],
            );

            $displayName = Str::of($roleName)->replace('_', ' ')->title()->toString();
            $email = $roleName.'@test.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $displayName.' User',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            // Attach to matching team if it exists and user isn't already on it.
            $team = Team::where('name', $teamName)->first();
            if ($team && ! $user->belongsToTeam($team)) {
                $user->teams()->attach($team, ['role' => 'member']);
                $user->switchTeam($team);
            }
        }

        User::query()
            ->where('is_system_admin', true)
            ->each(function (User $user) use ($superAdminRole): void {
                if (! $user->hasRole($superAdminRole)) {
                    $user->assignRole($superAdminRole);
                }
            });
    }
}
