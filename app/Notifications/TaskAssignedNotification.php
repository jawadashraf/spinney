<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task
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
            ->subject('Task Assigned: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name)
            ->line('You have been assigned to a new task.')
            ->line('**Title:** '.$this->task->title)
            ->line('**Due Date:** '.($this->task->due_date ? $this->task->due_date->format('F j, Y') : 'N/A'))
            ->action('View Task', route('filament.app.resources.tasks.edit', ['record' => $this->task]))
            ->line('Thank you!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'message' => 'You were assigned to task: '.$this->task->title,
        ];
    }
}
