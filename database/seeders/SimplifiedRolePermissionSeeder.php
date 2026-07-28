<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SimplifiedRolePermissionSeeder extends Seeder
{
    public function run(?int $teamId = null): void
    {
        $teamId = $teamId ?? (env('TEAM_ID') ? (int) env('TEAM_ID') : null);
        // Define roles with their permissions
        $rolePermissions = [
            'liaison' => [
                'display_name' => 'Liaison',
                'permissions' => ['ViewAny:Company', 'ViewAny:Schedule', 'View:Schedule'],
            ],
            'volunteer_liaison' => [
                'display_name' => 'Volunteer Liaison',
                'permissions' => [
                    'ViewAny:Company',
                    'ViewAny:Schedule',
                    'View:Schedule',
                    'Create:Schedule',
                    'ViewAny:Task',
                    'View:Task',
                    'Update:Task',
                    'ViewAny:ServiceUser',
                    'View:ServiceUser',
                ],
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
            $attributes = ['name' => $roleName, 'guard_name' => 'web'];
            if ($teamId !== null) {
                $attributes['team_id'] = $teamId;
            }

            $role = Role::firstOrCreate($attributes);

            if ($config['permissions'] === '*') {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($config['permissions']);
            }

            $this->command?->info("Role '{$roleName}' created with permissions.");
        }

        $this->command?->info('All roles and permissions have been seeded successfully.');
    }
}
