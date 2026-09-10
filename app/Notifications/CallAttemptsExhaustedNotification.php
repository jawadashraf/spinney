<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CallAttemptsExhaustedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Call $call) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @param  object{name: string}  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Service user unreachable: '.$this->serviceUserName())
            ->greeting('Hello '.$notifiable->name)
            ->line("{$this->serviceUserName()} could not be reached after {$this->call->attempt_count} attempts.")
            ->line('**Liaison:** '.($this->call->assignee->name ?? 'Unassigned'))
            ->line('**Last outcome:** '.($this->call->outcome?->getLabel() ?? '—'))
            ->action('View call', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Service user unreachable')
            ->body("{$this->serviceUserName()} could not be reached after {$this->call->attempt_count} attempts.")
            ->icon('heroicon-o-phone-x-mark')
            ->danger()
            ->actions([
                Action::make('view')->label('View call')->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function serviceUserName(): string
    {
        return $this->call->serviceUser->name ?? 'Service user';
    }

    private function url(): string
    {
        return CallResource::getUrl('view', ['record' => $this->call], tenant: $this->call->team);
    }
}
