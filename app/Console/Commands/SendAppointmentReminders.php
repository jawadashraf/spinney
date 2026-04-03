<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\People;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Command;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

final class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send reminders for appointments happening in 24 hours';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Schedule::query()
            ->with(['schedulable', 'periods'])
            ->ofType(ScheduleTypes::APPOINTMENT)
            ->where('start_date', $tomorrow)
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($appointments as $appointment) {
            // Notify the counselor
            $appointment->schedulable->notify(new AppointmentReminderNotification($appointment));

            // Notify the service user (if we have their user ID in metadata)
            if (isset($appointment->metadata['service_user_id'])) {
                $serviceUser = People::find($appointment->metadata['service_user_id']);
                if ($serviceUser && $serviceUser->user) {
                    $serviceUser->user->notify(new AppointmentReminderNotification($appointment));
                }
            }

            $count++;
        }

        $this->info("Sent reminders for {$count} appointments.");

        return Command::SUCCESS;
    }
}
