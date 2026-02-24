<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\People;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ServiceUserPromotedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public People $people)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Service User Promoted: '.$this->people->name)
            ->greeting('Hello!')
            ->line('A new enquiry has been promoted to a Service User.')
            ->line('Name: '.$this->people->name)
            ->action('View Profile', route('filament.app.resources.people.edit', $this->people))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New Service User Promoted')
            ->body($this->people->name.' has been promoted to a service user.')
            ->getDatabaseMessage();
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): mixed
    {
        return FilamentNotification::make()
            ->title('New Service User Promoted')
            ->body($this->people->name.' has been promoted to a service user.')
            ->success()
            ->getBroadcastMessage();
    }
}
