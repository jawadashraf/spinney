<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Restricts donations and Gift Aid declarations to managers and admins (AC DN-004). Managers record, edit and
 * report on donations; only admins delete them.
 */
final class DonationPermissions
{
    /**
     * @var list<string>
     */
    public const array MANAGER = [
        'ViewAny:Donation', 'View:Donation', 'Create:Donation', 'Update:Donation',
        'ViewAny:GiftAidDeclaration', 'View:GiftAidDeclaration', 'Create:GiftAidDeclaration', 'Update:GiftAidDeclaration',
    ];

    /**
     * @var list<string>
     */
    public const array ADMIN = [...self::MANAGER, 'Delete:Donation', 'Delete:GiftAidDeclaration'];

    /**
     * @return list<string>
     */
    public static function forRole(string $roleName): array
    {
        return match ($roleName) {
            'admin' => self::ADMIN,
            'manager' => self::MANAGER,
            default => [],
        };
    }

    /**
     * Create the permissions, grant each role its share, and revoke the rest from every role (optionally for one
     * team only).
     */
    public static function ensure(?int $teamId = null): void
    {
        foreach (self::ADMIN as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->where('name', '!=', 'super_admin')
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(function (Role $role): void {
                $granted = self::forRole($role->name);
                $held = $role->permissions->pluck('name')->all();

                $role->givePermissionTo($granted);
                $role->revokePermissionTo(array_values(array_intersect(array_diff(self::ADMIN, $granted), $held)));
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
