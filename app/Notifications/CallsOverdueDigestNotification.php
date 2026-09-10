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

final class CallsOverdueDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, int>  $overdueByLiaison  liaison name => overdue call count ('Unassigned' for none)
     */
    public function __construct(
        public Team $team,
        public array $overdueByLiaison,
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
        $message = (new MailMessage)
            ->subject('Overdue liaison calls: '.$this->total())
            ->greeting('Hello '.$notifiable->name)
            ->line('The following calls are more than 2 days overdue:');

        foreach ($this->overdueByLiaison as $liaison => $count) {
            $message->line("**{$liaison}:** {$count}");
        }

        return $message->action('Review overdue calls', CallResource::getUrl('index', tenant: $this->team));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $breakdown = collect($this->overdueByLiaison)
            ->map(fn (int $count, string $liaison): string => "{$liaison}: {$count}")
            ->implode(', ');

        return FilamentNotification::make()
            ->title($this->total().' liaison calls overdue')
            ->body($breakdown)
            ->icon('heroicon-o-exclamation-triangle')
            ->warning()
            ->actions([
                Action::make('review')->label('Review')->url(CallResource::getUrl('index', tenant: $this->team))->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function total(): int
    {
        return array_sum($this->overdueByLiaison);
    }
}
