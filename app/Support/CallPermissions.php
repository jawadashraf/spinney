<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions the Shield permissions used by the liaison calls feature and
 * grants them to the application roles.
 */
final class CallPermissions
{
    /**
     * @var list<string>
     */
    public const array CALL = [
        'ViewAny:Call', 'View:Call', 'Create:Call', 'Update:Call', 'Delete:Call', 'DeleteAny:Call',
        'Restore:Call', 'RestoreAny:Call', 'ForceDelete:Call', 'ForceDeleteAny:Call',
        'RecordOutcome:Call', 'Reschedule:Call', 'Reassign:Call',
    ];

    /**
     * @var list<string>
     */
    public const array CALL_PLAN = [
        'ViewAny:CallPlan', 'View:CallPlan', 'Create:CallPlan', 'Update:CallPlan', 'Delete:CallPlan', 'DeleteAny:CallPlan',
        'Restore:CallPlan', 'RestoreAny:CallPlan', 'ForceDelete:CallPlan', 'ForceDeleteAny:CallPlan',
    ];

    /**
     * @var list<string>
     */
    public const array LIAISON = [
        'ViewAny:Call', 'View:Call', 'RecordOutcome:Call',
        'ViewAny:CallPlan', 'View:CallPlan',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [...self::CALL, ...self::CALL_PLAN];
    }

    /**
     * @return list<string>
     */
    public static function forRole(string $roleName): array
    {
        return match ($roleName) {
            'admin', 'manager' => self::all(),
            'liaison', 'volunteer_liaison' => self::LIAISON,
            default => [],
        };
    }

    /**
     * Create the permissions and grant them to matching roles (optionally for one team only).
     */
    public static function ensure(?int $teamId = null): void
    {
        foreach (self::all() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::query()
            ->whereIn('name', ['admin', 'manager', 'liaison', 'volunteer_liaison'])
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->each(fn (Role $role) => $role->givePermissionTo(self::forRole($role->name)));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
