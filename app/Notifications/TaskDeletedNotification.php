<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $taskTitle
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @param  object{name: string}  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Deleted: '.$this->taskTitle)
            ->greeting('Hello '.$notifiable->name)
            ->line('A task you were associated with has been deleted.')
            ->line('**Title:** '.$this->taskTitle)
            ->line('Thank you!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'task_id' => null,
            'message' => 'Task was deleted: '.$this->taskTitle,
        ];
    }
}
