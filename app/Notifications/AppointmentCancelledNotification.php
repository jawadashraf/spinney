<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Schedule;
use App\Notifications\Concerns\AppointmentNotificationContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AppointmentCancelledNotification extends Notification implements ShouldQueue
{
    use AppointmentNotificationContent;
    use Queueable;

    public function __construct(public Schedule $schedule) {}

    protected function subject(): string
    {
        return 'Appointment cancelled';
    }

    protected function headline(): string
    {
        return 'The following appointment has been cancelled:';
    }

    protected function icon(): string
    {
        return 'heroicon-o-x-circle';
    }

    /**
     * The reason may contain internal detail, so only staff see it.
     */
    protected function addExtraLines(MailMessage $mail, bool $isStaff): void
    {
        if ($isStaff && filled($reason = $this->schedule->metadata['cancellation_reason'] ?? null)) {
            $mail->line('**Reason:** '.$reason);
        }
    }
}
