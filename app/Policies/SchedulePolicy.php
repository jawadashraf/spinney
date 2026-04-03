<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use Zap\Enums\ScheduleTypes;

final class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['counselor', 'management', 'admin']);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($schedule->schedule_type === ScheduleTypes::AVAILABILITY) {
            if ($schedule->metadata['is_locked'] ?? false) {
                return $user->hasAnyRole(['management', 'admin']);
            }

            return $user->hasAnyRole(['counselor', 'management', 'admin'])
                || ($user->id === $schedule->schedulable_id);
        }

        if ($schedule->schedule_type === ScheduleTypes::APPOINTMENT) {
            return $user->hasAnyRole(['management', 'admin']);
        }

        return $user->hasAnyRole(['management', 'admin']);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($schedule->metadata['is_locked'] ?? false) {
            return false;
        }

        if ($schedule->schedule_type === ScheduleTypes::AVAILABILITY) {
            return $user->hasAnyRole(['counselor', 'management', 'admin'])
                || ($user->id === $schedule->schedulable_id);
        }

        return $user->hasAnyRole(['management', 'admin']);
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->hasAnyRole(['management', 'admin']);
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('admin');
    }
}
