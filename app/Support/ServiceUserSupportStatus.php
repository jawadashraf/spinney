<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SupportStatus;
use App\Events\ServiceUserNeedsAttention;
use App\Models\Note;
use App\Models\ServiceUser;

final class ServiceUserSupportStatus
{
    /**
     * Escalate a service user's support status from a note and alert management.
     * Urgent always wins; "needs attention" never downgrades an urgent flag.
     */
    public static function flag(ServiceUser $serviceUser, Note $note, SupportStatus $status): void
    {
        $profile = $serviceUser->profile;

        if ($profile !== null) {
            $profileStatus = $profile->support_status;

            if ($status === SupportStatus::UrgentAttention) {
                $profile->update([
                    'support_status' => SupportStatus::UrgentAttention,
                    'support_flagged_at' => now(),
                    'support_resolved_at' => null,
                ]);
            } elseif ($status === SupportStatus::NeedsAttention && $profileStatus !== SupportStatus::UrgentAttention) {
                $profile->update([
                    'support_status' => SupportStatus::NeedsAttention,
                    'support_flagged_at' => now(),
                    'support_resolved_at' => null,
                ]);
            }
        }

        if (in_array($status, [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention], true)) {
            event(new ServiceUserNeedsAttention($serviceUser, $note, $status));
        }
    }
}
