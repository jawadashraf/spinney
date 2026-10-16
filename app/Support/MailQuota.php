<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OutgoingMail;

/**
 * Tracks the mailbox's rolling 24-hour sending limit, keeping a share of it for system notifications.
 *
 * Notifications draw from their reserve first; only notification sending beyond the reserve
 * reduces what outreach emails may use.
 */
final class MailQuota
{
    public const string OUTREACH_HEADER = 'X-Spinney-Outreach';

    public function dailyLimit(): int
    {
        return (int) config('outreach.daily_limit');
    }

    public function reservedForNotifications(): int
    {
        return min((int) config('outreach.reserved_for_notifications'), $this->dailyLimit());
    }

    public function used(): int
    {
        return $this->usedByOutreach() + $this->usedByNotifications();
    }

    public function usedByOutreach(): int
    {
        return $this->sumSince(outreach: true);
    }

    public function usedByNotifications(): int
    {
        return $this->sumSince(outreach: false);
    }

    public function availableForOutreach(): int
    {
        $notificationShare = max($this->reservedForNotifications(), $this->usedByNotifications());

        return max(0, $this->dailyLimit() - $notificationShare - $this->usedByOutreach());
    }

    public function summary(): string
    {
        return sprintf(
            'Last 24 hours: %d of %d emails sent · %d available for outreach · %d kept for notifications',
            $this->used(),
            $this->dailyLimit(),
            $this->availableForOutreach(),
            $this->reservedForNotifications(),
        );
    }

    public function record(int $recipients, bool $isOutreach): void
    {
        OutgoingMail::query()->create([
            'recipients' => $recipients,
            'is_outreach' => $isOutreach,
            'sent_at' => now(),
        ]);
    }

    private function sumSince(bool $outreach): int
    {
        return (int) OutgoingMail::query()
            ->where('is_outreach', $outreach)
            ->where('sent_at', '>', now()->subDay())
            ->sum('recipients');
    }
}
