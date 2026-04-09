<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Team;

final class SimplifiedRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $teamId = Team::first()->id;
        // Define roles with their permissions
        $rolePermissions = [
            'liaison' => [
                'display_name' => 'Liaison',
                'permissions' => ['ViewAny:Company', 'ViewAny:Schedule', 'View:Schedule'],
            ],
            'assessor' => [
                'display_name' => 'Assessor',
                'permissions' => ['ViewAny:Company', 'ViewAny:Schedule', 'View:Schedule'],
            ],
            'counselor' => [
                'display_name' => 'Counselor',
                'permissions' => ['ViewAny:Company', 'ViewAny:Schedule', 'View:Schedule', 'Create:Schedule',
                    'Update:Schedule', 'Delete:Schedule'],
            ],
//            'aftercare' => [
//                'display_name' => 'Aftercare',
//                'permissions' => ['view_any_schedule', 'view_schedule'],
//            ],
//            'safeguarding' => [
//                'display_name' => 'Safeguarding',
//                'permissions' => ['view_any_schedule', 'view_schedule'],
//            ],
//            'fundraising' => [
//                'display_name' => 'Fundraising',
//                'permissions' => ['view_any_schedule', 'view_schedule'],
//            ],
            'manager' => [
                'display_name' => 'Manager',
                'permissions' => [
                    'ViewAny:Company',
                    'ViewAny:Schedule',
                    'View:Schedule',
                    'Create:Schedule',
                    'Update:Schedule',
                    'Delete:Schedule',
                    'Lock:Schedule',
                    'Unlock:Schedule',
                ],
            ],
            'admin' => [
                'display_name' => 'Admin',
                'permissions' => '*', // All permissions
            ],
        ];

        setPermissionsTeamId($teamId);

        foreach ($rolePermissions as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            if ($config['permissions'] === '*') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($config['permissions']);
            }

            $this->command->info("Role '{$roleName}' created with permissions.");
        }

        $this->command->info('All roles and permissions have been seeded successfully.');
    }
}
