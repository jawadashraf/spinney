<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Note;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskNoteAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public Note $note
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
        $creatorName = $this->note->user ? $this->note->user->name : 'Someone';

        return (new MailMessage)
            ->subject('New Note Added to Task: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name)
            ->line($creatorName.' added a new note to a task you are associated with.')
            ->line('**Title:** '.$this->task->title)
            ->action('View Task', route('filament.app.resources.tasks.edit', ['record' => $this->task]))
            ->line('Thank you!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $creatorName = $this->note->user ? $this->note->user->name : 'Someone';

        return [
            'task_id' => $this->task->id,
            'note_id' => $this->note->id,
            'message' => $creatorName.' added a note to: '.$this->task->title,
        ];
    }
}
