<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\CallPlans\CallPlanResource;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CallPlansWithoutCallsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const int MAIL_NAME_LIMIT = 20;

    public const int DATABASE_NAME_LIMIT = 5;

    /**
     * @param  list<string>  $serviceUserNames  service users whose active call plan has no open call
     */
    public function __construct(
        public Team $team,
        public array $serviceUserNames,
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
            ->subject('Call plans with no call booked: '.count($this->serviceUserNames))
            ->greeting('Hello '.$notifiable->name)
            ->line('These service users have an active call plan but no call booked. Schedule a call, or pause the plan if calls should stop:');

        foreach (array_slice($this->serviceUserNames, 0, self::MAIL_NAME_LIMIT) as $serviceUserName) {
            $message->line('• '.$serviceUserName);
        }

        if ($this->remaining(self::MAIL_NAME_LIMIT) > 0) {
            $message->line('…and '.$this->remaining(self::MAIL_NAME_LIMIT).' more.');
        }

        return $message->action('Review call plans', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = count($this->serviceUserNames);

        $body = implode(', ', array_slice($this->serviceUserNames, 0, self::DATABASE_NAME_LIMIT))
            .($this->remaining(self::DATABASE_NAME_LIMIT) > 0 ? ' and '.$this->remaining(self::DATABASE_NAME_LIMIT).' more' : '');

        return FilamentNotification::make()
            ->title($count.' '.str('call plan')->plural($count).' with no call booked')
            ->body($body)
            ->icon('heroicon-o-calendar-days')
            ->warning()
            ->actions([
                Action::make('review')->label('Review')->url($this->url())->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function remaining(int $limit): int
    {
        return max(0, count($this->serviceUserNames) - $limit);
    }

    private function url(): string
    {
        return CallPlanResource::getUrl('index', tenant: $this->team);
    }
}
