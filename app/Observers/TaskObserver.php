<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;
use App\Notifications\TaskDeletedNotification;

final readonly class TaskObserver
{
    public function creating(Task $task): void
    {
        if (auth('web')->check()) {
            $task->creator_id = auth('web')->id();
        }
    }

    public function saved(Task $task): void
    {
        $task->invalidateRelatedSummaries();
    }

    public function deleted(Task $task): void
    {
        $task->invalidateRelatedSummaries();

        foreach ($task->assignees as $assignee) {
            $assignee->notify(new TaskDeletedNotification($task->title));
        }
    }
}
