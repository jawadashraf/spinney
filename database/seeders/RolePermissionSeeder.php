<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's role permissions.
     */
    public function run(): void
    {
        // Define permissions to assign to roles.
        $rolePermissions = [
            'frontline' => [
                'ViewAny:Company',
            ],
            'assessment' => [
                'ViewAny:Company',
            ],
            'drug_alcohol' => [
                'ViewAny:Company',
            ],
            'spiritual' => [
                'ViewAny:Company',
            ],
            'education_outreach' => [
                'ViewAny:Company',
            ],
            'aftercare' => [
                'ViewAny:Company',
            ],
            'safeguarding' => [
                'ViewAny:Company',
            ],
            'fundraising' => [
                'ViewAny:Company',
            ],
            'management' => [
                'ViewAny:Company',
            ],
            'admin' => [
                'ViewAny:Company',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');

            if (! $role) {
                $this->command->warn("Role '{$roleName}' not found. Skipping.");

                continue;
            }

            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web']
                );

                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
