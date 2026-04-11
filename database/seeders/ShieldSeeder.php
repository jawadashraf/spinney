<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
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
            ['name' => Utils::getSuperAdminName(), 'team_id' => null],
            ['guard_name' => 'web'],
        );

        $team = Team::where('name', 'Spinney Hill')->first();
        if ($team) {
            setPermissionsTeamId($team->id);
        }

        $departmentNames = ['Management', 'Liaison', 'Counselor'];

        foreach ($departmentNames as $departmentName) {
            Department::firstOrCreate(
                ['name' => $departmentName],
                ['team_id' => $team->id],
            );
        }

        // Application roles from the Phase 1 Handoff Permissions Matrix.
        // Maps role name => matching team name for team assignment.
        $applicationRoles = [
            'admin' => 'Management',
            'manager' => 'Management',
            'liaison' => 'Liaison',
            'counselor' => 'Counselor',
            'service_user' => '',
            //            'assessor' => 'Assessment',
            //            'drug_alcohol' => 'Drug & Alcohol',
            //            'spiritual' => 'Spiritual Counselling',
            //            'education_outreach' => 'Education & Outreach',
            //            'aftercare' => 'Aftercare',
            //            'safeguarding' => 'Safeguarding',
            //            'fundraising' => 'Fundraising',
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

            $user->teams()->syncWithoutDetaching([$team->id => ['role' => 'member']]);
            $user->switchTeam($team);

            // Attach to matching team if it exists and user isn't already on it.
            $department = Department::where('name', $departmentName)->first();
            if ($department) {
                $user->departments()->attach($department, ['team_id' => $team->id]);
            }
        }

        // Assign the super_admin role and sync permissions for other roles.
        if ($team) {
            setPermissionsTeamId($team->id);

            // Sync ALL permissions to admin and manager roles for this team
            $allPermissions = Permission::all();

            Role::whereIn('name', ['admin', 'manager'])
                ->where('team_id', $team->id)
                ->each(function (Role $role) use ($allPermissions): void {
                    $role->syncPermissions($allPermissions);
                });

            User::query()
                ->where('is_system_admin', true)
                ->each(function (User $user) use ($superAdminRole): void {
                    if (! $user->hasRole($superAdminRole)) {
                        $user->assignRole($superAdminRole);
                    }
                });
        }
    }
}
