<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zap\Models\Schedule;

final class AppointmentCancelledNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject('Appointment Cancelled')
            ->greeting('Hello '.$notifiable->name)
            ->line('An appointment has been cancelled:')
            ->line('**Date:** '.$this->schedule->start_date->format('F j, Y'))
            ->line('**Time:** '.$startTime.' - '.$endTime)
            ->line('**Counselor:** '.$this->schedule->schedulable->name)
            ->line('**Service User:** '.($this->schedule->metadata['service_user_name'] ?? 'N/A'));

        if (isset($this->schedule->metadata['cancellation_reason'])) {
            $mail->line('**Reason:** '.$this->schedule->metadata['cancellation_reason']);
        }

        $mail->action('View Details', route('filament.app.resources.schedules.view', $this->schedule))
            ->line('Thank you!');

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $periods = $this->schedule->periods;

        return [
            'schedule_id' => $this->schedule->id,
            'message' => 'Appointment cancelled for '.$this->schedule->start_date->format('F j, Y'),
            'date' => $this->schedule->start_date->format('Y-m-d'),
            'time' => $periods->first()?->start_time ?? 'N/A',
            'reason' => $this->schedule->metadata['cancellation_reason'] ?? null,
        ];
    }
}
