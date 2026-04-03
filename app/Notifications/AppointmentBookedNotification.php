<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zap\Models\Schedule;

final class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Schedule $schedule
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $periods = $this->schedule->periods;
        $startTime = $periods->first()?->start_time ?? 'N/A';
        $endTime = $periods->first()?->end_time ?? 'N/A';

        return (new MailMessage)
            ->subject('Appointment Booked')
            ->greeting('Hello '.$notifiable->name)
            ->line('A new appointment has been booked:')
            ->line('**Date:** '.$this->schedule->start_date->format('F j, Y'))
            ->line('**Time:** '.$startTime.' - '.$endTime)
            ->line('**Counselor:** '.$this->schedule->schedulable->name)
            ->line('**Service User:** '.($this->schedule->metadata['service_user_name'] ?? 'N/A'))
            ->action('View Appointment', route('filament.app.resources.schedules.view', $this->schedule))
            ->line('Thank you!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $periods = $this->schedule->periods;

        return [
            'schedule_id' => $this->schedule->id,
            'message' => 'New appointment booked for '.$this->schedule->start_date->format('F j, Y'),
            'date' => $this->schedule->start_date->format('Y-m-d'),
            'time' => $periods->first()?->start_time ?? 'N/A',
        ];
    }
}
