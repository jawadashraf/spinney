<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public bool $isOverdue = false
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
        $subject = $this->isOverdue
            ? 'OVERDUE Task: '.$this->task->title
            : 'Reminder: Task Due Soon: '.$this->task->title;

        $message = $this->isOverdue
            ? 'A task you are associated with is now overdue.'
            : 'A task you are associated with is due within the next 24 hours.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name)
            ->line($message)
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
            'message' => ($this->isOverdue ? 'Overdue: ' : 'Due Soon: ').$this->task->title,
        ];
    }
}
