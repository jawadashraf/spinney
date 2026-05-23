<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Enquiry;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SafeguardingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $callerName = $this->enquiry->people?->name ?? ($this->enquiry->caller_note ?: 'Anonymous');
        $category = $this->enquiry->category?->value ?? 'N/A';

        return (new MailMessage)
            ->subject('Safeguarding Flag Raised on Enquiry #'.$this->enquiry->id)
            ->greeting('A safeguarding concern has been flagged.')
            ->line('A safeguarding flag has been raised on an enquiry that requires your attention.')
            ->line('**Enquiry ID:** '.$this->enquiry->id)
            ->line('**Caller:** '.$callerName)
            ->line('**Category:** '.$category)
            ->line('**Reason for Contact:** '.($this->enquiry->reason_for_contact ?: 'N/A'))
            ->line($this->enquiry->risk_flags ? '**Risk Flags:** '.$this->enquiry->risk_flags : '')
            ->action('View Enquiry', route('filament.app.resources.enquiries.view', ['tenant' => $this->enquiry->team, 'record' => $this->enquiry]))
            ->line('Please review and take appropriate action.');
    }

    public function toArray(object $notifiable): array
    {
        $callerName = $this->enquiry->people?->name ?? ($this->enquiry->caller_note ?: 'Anonymous');

        return FilamentNotification::make()
            ->title('Safeguarding Flag Raised')
            ->body('Enquiry #'.$this->enquiry->id.' from '.$callerName.' has safeguarding concerns flagged.')
            ->danger()
            ->getDatabaseMessage();
    }
}
