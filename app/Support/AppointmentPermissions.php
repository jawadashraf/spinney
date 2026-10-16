<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lets counsellors and assessors reach the Appointments screens (AC AP-006). Which appointments they see is
 * limited to their own by AppointmentResource; managers and admins hold every permission already.
 */
final class AppointmentPermissions
{
    /**
     * @var list<string>
     */
    public const array COUNSELLOR = ['ViewAny:Appointment', 'View:Appointment', 'Update:Appointment'];

    /**
     * @var list<string>
     */
    public const array ASSESSOR = ['ViewAny:Appointment', 'View:Appointment'];

    /**
     * @return list<string>
     */
    public static function forRole(string $roleName): array
    {
        return match ($roleName) {
            'counselor' => self::COUNSELLOR,
            'assessor' => self::ASSESSOR,
            default => [],
        };
    }

    /**
     * Create the permissions and grant them to counsellor and assessor roles (optionally for one team only).
     */
    public static function ensure(?int $teamId = null): void
    {
        foreach (self::COUNSELLOR as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->whereIn('name', ['counselor', 'assessor'])
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(fn (Role $role) => $role->givePermissionTo(self::forRole($role->name)));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
