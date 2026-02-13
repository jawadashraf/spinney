<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

final class ShieldSeeder extends Seeder
{
    /**
     * Seed the Shield roles and assign super_admin to system administrators.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdminRole = Role::firstOrCreate(
            ['name' => Utils::getSuperAdminName()],
            ['guard_name' => 'web'],
        );

        Role::firstOrCreate(
            ['name' => Utils::getPanelUserRoleName()],
            ['guard_name' => 'web'],
        );

        User::query()
            ->where('is_system_admin', true)
            ->each(function (User $user) use ($superAdminRole): void {
                if (! $user->hasRole($superAdminRole)) {
                    $user->assignRole($superAdminRole);
                }
            });
    }
}
