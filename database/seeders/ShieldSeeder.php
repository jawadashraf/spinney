<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Department;
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
        //        $this->command->call('shield:generate', [
        //            '--all' => true,
        //            '--ignore-existing-policies' => true,
        //            '--no-interaction' => true,
        //            '--panel' => 'app',
        //        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);

        $superAdminRole = Role::firstOrCreate(
            ['name' => Utils::getSuperAdminName()],
            ['guard_name' => 'web'],
        );

        $team = Team::where('name', 'Spinney Hill')->first();
        if ($team) {
            setPermissionsTeamId($team->id);
        }

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

        foreach ($applicationRoles as $roleName => $departmentName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'team_id' => $team?->id],
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

            $user->teams()->attach($team, ['role' => 'member']);
            $user->switchTeam($team);

            // Attach to matching team if it exists and user isn't already on it.
            $department = Department::where('name', $departmentName)->first();
            if ($department) {
                $user->departments()->attach($department);
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
