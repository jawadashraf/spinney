<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Restricts the companies (outreach) directory and outreach emails to managers and admins.
 */
final class CompanyPermissions
{
    /**
     * @var list<string>
     */
    public const array ALL = [
        'ViewAny:Company', 'View:Company', 'Create:Company', 'Update:Company', 'Delete:Company',
        'Restore:Company', 'RestoreAny:Company', 'ForceDelete:Company', 'ForceDeleteAny:Company',
        'Replicate:Company', 'Reorder:Company',
        'ViewAny:OutreachEmail', 'View:OutreachEmail', 'Create:OutreachEmail', 'Update:OutreachEmail',
        'Delete:OutreachEmail', 'Send:OutreachEmail',
    ];

    /**
     * @var list<string>
     */
    public const array DIRECTORY_ROLES = ['admin', 'manager'];

    /**
     * Create the permissions, grant them to managers and admins, and revoke them from every other role
     * (optionally for one team only).
     */
    public static function ensure(?int $teamId = null): void
    {
        foreach (self::ALL as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->where('name', '!=', 'super_admin')
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(function (Role $role): void {
                if (in_array($role->name, self::DIRECTORY_ROLES, true)) {
                    $role->givePermissionTo(self::ALL);

                    return;
                }

                $role->revokePermissionTo(array_values(array_intersect(self::ALL, $role->permissions->pluck('name')->all())));
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
