<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SafeguardingFlagRaised;
use App\Models\User;
use App\Notifications\SafeguardingNotification;

final readonly class NotifyManagementOfSafeguardingFlag
{
    public function handle(SafeguardingFlagRaised $event): void
    {
        $enquiry = $event->enquiry;

        $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new SafeguardingNotification($enquiry));
        }
    }
}
