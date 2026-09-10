<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class CallAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Call $call,
        public int $count = 1,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $body = $this->count > 1
            ? "{$this->count} calls have been assigned to you."
            : ($this->call->serviceUser->name ?? 'A service user').' – due '.$this->call->due_at->format('d M Y, H:i');

        $url = $this->count > 1
            ? CallResource::getUrl('index', tenant: $this->call->team)
            : CallResource::getUrl('view', ['record' => $this->call], tenant: $this->call->team);

        return FilamentNotification::make()
            ->title($this->count > 1 ? 'Calls assigned to you' : 'Call assigned to you')
            ->body($body)
            ->icon('heroicon-o-phone')
            ->info()
            ->actions([
                Action::make('view')->label('Open')->url($url)->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
