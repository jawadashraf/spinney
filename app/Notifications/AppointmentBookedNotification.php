<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Schedule;
use App\Notifications\Concerns\AppointmentNotificationContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use AppointmentNotificationContent;
    use Queueable;

    public function __construct(public Schedule $schedule) {}

    protected function subject(): string
    {
        return 'Appointment booked';
    }

    protected function headline(): string
    {
        return 'An appointment has been booked:';
    }

    protected function icon(): string
    {
        return 'heroicon-o-calendar';
    }
}
