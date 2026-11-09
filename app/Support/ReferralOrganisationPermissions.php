<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions Shield permissions for the referral directory organisations.
 * Grants read access to liaisons and volunteer liaisons; grants full management
 * to admins and managers.
 */
final class ReferralOrganisationPermissions
{
    /**
     * @var list<string>
     */
    public const array ALL = [
        'ViewAny:ReferralOrganisation',
        'View:ReferralOrganisation',
        'Create:ReferralOrganisation',
        'Update:ReferralOrganisation',
        'Delete:ReferralOrganisation',
        'DeleteAny:ReferralOrganisation',
        'Restore:ReferralOrganisation',
        'RestoreAny:ReferralOrganisation',
        'ForceDelete:ReferralOrganisation',
        'ForceDeleteAny:ReferralOrganisation',
    ];

    /**
     * @var list<string>
     */
    public const array CALL_VOLUNTEER_ROLES = [
        'liaison',
        'volunteer_liaison',
    ];

    /**
     * @var list<string>
     */
    public const array MANAGEMENT_ROLES = [
        'admin',
        'manager',
    ];

    public static function ensure(?int $teamId = null): void
    {
        foreach (self::ALL as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->where('name', '!=', 'super_admin')
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(function (Role $role): void {
                if (in_array($role->name, self::MANAGEMENT_ROLES, true)) {
                    $role->givePermissionTo(self::ALL);

                    return;
                }

                if (in_array($role->name, self::CALL_VOLUNTEER_ROLES, true)) {
                    $role->givePermissionTo([
                        'ViewAny:ReferralOrganisation',
                        'View:ReferralOrganisation',
                    ]);

                    return;
                }

                $role->revokePermissionTo(
                    array_values(array_intersect(self::ALL, $role->permissions->pluck('name')->all()))
                );
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

