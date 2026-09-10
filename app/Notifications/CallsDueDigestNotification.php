<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CallsDueDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Team $team,
        public int $dueToday,
        public int $overdue,
    ) {}

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
            ->subject('Your calls for today')
            ->greeting('Hello '.$notifiable->name)
            ->line("**Due today:** {$this->dueToday}")
            ->line("**Overdue:** {$this->overdue}")
            ->action('Open my calls', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Your calls for today')
            ->body("{$this->dueToday} due today, {$this->overdue} overdue.")
            ->icon('heroicon-o-phone')
            ->color($this->overdue > 0 ? 'danger' : 'info')
            ->actions([
                Action::make('open')->label('Open my calls')->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function url(): string
    {
        return CallResource::getUrl('index', tenant: $this->team);
    }
}
