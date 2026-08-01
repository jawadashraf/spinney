<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ServiceUserNeedsAttention;
use App\Models\User;
use App\Notifications\ServiceUserNeedsAttentionNotification;

final readonly class NotifyManagersOfServiceUserNeedsAttention
{
    public function handle(ServiceUserNeedsAttention $event): void
    {
        if ($event->serviceUser->team_id) {
            setPermissionsTeamId($event->serviceUser->team_id);
        }

        $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new ServiceUserNeedsAttentionNotification(
                $event->serviceUser,
                $event->note,
                $event->status
            ));
        }
    }
}
