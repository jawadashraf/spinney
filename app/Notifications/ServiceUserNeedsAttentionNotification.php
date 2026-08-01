<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\SupportStatus;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\Note;
use App\Models\ServiceUser;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ServiceUserNeedsAttentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceUser $serviceUser,
        public Note $note,
        public SupportStatus $status,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->status->getLabel();
        $noteSnippet = strip_tags((string) $this->note->body);

        $url = ServiceUserResource::getUrl('edit', [
            'tenant' => $this->serviceUser->team_id,
            'record' => $this->serviceUser,
        ]);

        return (new MailMessage)
            ->subject("[Attention Required] Service User: {$this->serviceUser->name} ({$statusLabel})")
            ->greeting('Attention Required')
            ->line("A support note has flagged that service user **{$this->serviceUser->name}** requires attention.")
            ->line("**Status:** {$statusLabel}")
            ->line("**Note Title:** {$this->note->title}")
            ->line("**Note Details:** {$noteSnippet}")
            ->action('View Service User Profile', $url)
            ->line('Please review and take appropriate action.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title("Service User Flagged: {$this->serviceUser->name}")
            ->body("Flagged as {$this->status->getLabel()}. Note: {$this->note->title}");

        if ($this->status === SupportStatus::UrgentAttention) {
            $notification->danger();
        } else {
            $notification->warning();
        }

        return $notification->getDatabaseMessage();
    }
}
