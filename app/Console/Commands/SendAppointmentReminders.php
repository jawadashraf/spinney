<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Notifications\AppointmentReminderNotification;
use App\Support\AppointmentRecipients;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Zap\Enums\ScheduleTypes;

#[Description('Send reminders for appointments happening tomorrow')]
#[Signature('appointments:send-reminders')]
final class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $appointments = Schedule::whereAppointmentStatus(
            Schedule::query()
                ->with(['schedulable', 'periods', 'team'])
                ->ofType(ScheduleTypes::APPOINTMENT)
                ->whereDate('start_date', now()->addDay()->toDateString())
                ->where('is_active', true),
            Schedule::OPEN_STATUSES,
        )->get();

        foreach ($appointments as $appointment) {
            /** @var Schedule $appointment */
            AppointmentRecipients::notify($appointment, new AppointmentReminderNotification($appointment));
        }

        $this->info("Sent reminders for {$appointments->count()} appointments.");

        return self::SUCCESS;
    }
}
