<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SimplifiedRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define roles with their permissions
        $rolePermissions = [
            'frontline' => [
                'display_name' => 'Frontline',
                'permissions' => ['view_any_schedule', 'view_schedule'],
            ],
            'assessment' => [
                'display_name' => 'Assessment',
                'permissions' => ['view_any_schedule', 'view_schedule'],
            ],
            'counselor' => [
                'display_name' => 'Counselor',
                'permissions' => [
                    'view_any_schedule',
                    'view_schedule',
                    'create_schedule',
                    'update_schedule',
                    'delete_schedule',
                ],
            ],
            'aftercare' => [
                'display_name' => 'Aftercare',
                'permissions' => ['view_any_schedule', 'view_schedule'],
            ],
            'safeguarding' => [
                'display_name' => 'Safeguarding',
                'permissions' => ['view_any_schedule', 'view_schedule'],
            ],
            'fundraising' => [
                'display_name' => 'Fundraising',
                'permissions' => ['view_any_schedule', 'view_schedule'],
            ],
            'management' => [
                'display_name' => 'Management',
                'permissions' => [
                    'view_any_schedule',
                    'view_schedule',
                    'create_schedule',
                    'update_schedule',
                    'delete_schedule',
                    'lock_schedule',
                    'unlock_schedule',
                ],
            ],
            'admin' => [
                'display_name' => 'Administration',
                'permissions' => '*', // All permissions
            ],
        ];

        foreach ($rolePermissions as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );

            if ($config['permissions'] === '*') {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo($config['permissions']);
            }

            $this->command->info("Role '{$roleName}' created with permissions.");
        }

        $this->command->info('All roles and permissions have been seeded successfully.');
    }
}
