<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared content for appointment emails (used by the booked, reminder and cancelled notifications). Staff get an in-app notification and a link to the appointment;
 * attendees (service users or external people, sent to their own address) get the details only, since they
 * cannot open the CRM.
 */
trait AppointmentNotificationContent
{
    abstract protected function subject(): string;

    abstract protected function headline(): string;

    abstract protected function icon(): string;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isStaff = $notifiable instanceof User;

        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.($isStaff ? $notifiable->name : $this->schedule->attendeeName()).',')
            ->line($this->headline());

        foreach ($this->details($isStaff) as $label => $value) {
            $mail->line("**{$label}:** {$value}");
        }

        $this->addExtraLines($mail, $isStaff);

        if ($isStaff && ($url = $this->url()) !== null) {
            return $mail->action('View appointment', $url);
        }

        return $mail->line('If you need to change or cancel this appointment, please contact Spinney Hill Support Centre.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->subject())
            ->body($this->schedule->attendeeName().' · '.$this->when())
            ->icon($this->icon());

        if (($url = $this->url()) !== null) {
            $notification->actions([
                Action::make('view')->label('Open')->url($url)->markAsRead(),
            ]);
        }

        return $notification->getDatabaseMessage();
    }

    protected function addExtraLines(MailMessage $mail, bool $isStaff): void {}

    /**
     * @return array<string, string>
     */
    protected function details(bool $isStaff): array
    {
        $details = [
            'Date' => $this->schedule->start_date->format('l j F Y'),
            'Time' => $this->schedule->timeRange(),
            'With' => (string) data_get($this->schedule, 'schedulable.name', '-'),
        ];

        if (($type = $this->schedule->getCounselorType()) !== null) {
            $details['Type'] = $type->getLabel();
        }

        if ($isStaff) {
            $details['Attendee'] = $this->schedule->attendeeName();
        }

        return $details;
    }

    protected function when(): string
    {
        return $this->schedule->start_date->format('D j M').', '.$this->schedule->timeRange();
    }

    protected function url(): ?string
    {
        $team = $this->schedule->team;

        return $team === null ? null : AppointmentResource::getUrl('view', ['record' => $this->schedule], tenant: $team);
    }
}
