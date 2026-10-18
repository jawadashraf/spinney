<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Donation;
use App\Models\People;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Enforces role-based permissions for Reporting and Data Exports (AC RP-005).
 *
 * Permission Matrix:
 * - Admin (AD): Read/Write reports, Export all resources
 * - Manager (MG): Read reports, Export all resources
 * - Safeguarding (SG): Read Enquiry and Engagement reports, No export
 * - Fundraising (FR): Read/Write Donation reports, Export donors (People / Donations)
 * - Frontline (FL) & other roles: No reports, No export
 */
final class ReportPermissions
{
    /**
     * @var list<string>
     */
    public const array REPORT_PERMISSIONS = [
        'View:EnquiryReport',
        'View:EngagementReport',
        'View:DonationReport',
        'ViewAny:Enquiry',
        'View:Enquiry',
        'ViewAny:ServiceUser',
        'View:ServiceUser',
        'ViewAny:Call',
        'View:Call',
        'Export:Enquiry',
        'Export:ServiceUser',
        'Export:Call',
        'Export:People',
        'Export:Company',
        'Export:Donation',
    ];

    public static function canViewReports(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return self::canViewEnquiryReport($user)
            || self::canViewEngagementReport($user)
            || self::canViewDonationReport($user);
    }

    public static function canViewEnquiryReport(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole(['admin', 'manager', 'safeguarding'])) {
            return true;
        }

        return $user->can('View:EnquiryReport');
    }

    public static function canViewEngagementReport(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole(['admin', 'manager', 'safeguarding'])) {
            return true;
        }

        return $user->can('View:EngagementReport');
    }

    public static function canViewDonationReport(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole(['admin', 'manager', 'fundraising'])) {
            return true;
        }

        return $user->can('View:DonationReport');
    }

    public static function canExport(?User $user, ?string $modelClass = null): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        // Fundraising role may only export donors (People and Donation)
        if ($user->hasRole('fundraising')) {
            return in_array($modelClass, [People::class, Donation::class], true);
        }

        return false;
    }

    /**
     * Seed or sync reporting permissions across roles for a given team.
     */
    public static function ensure(?int $teamId = null): void
    {
        foreach (self::REPORT_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->where('name', '!=', 'super_admin')
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(function (Role $role): void {
                $granted = match ($role->name) {
                    'admin', 'manager' => self::REPORT_PERMISSIONS,
                    'safeguarding' => [
                        'View:EnquiryReport',
                        'View:EngagementReport',
                        'ViewAny:Enquiry',
                        'View:Enquiry',
                    ],
                    'fundraising' => [
                        'View:DonationReport',
                        'Export:People',
                        'Export:Donation',
                    ],
                    default => [],
                };

                $held = $role->permissions->pluck('name')->all();
                $role->givePermissionTo($granted);
                $role->revokePermissionTo(array_values(array_intersect(array_diff(self::REPORT_PERMISSIONS, $granted), $held)));
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
