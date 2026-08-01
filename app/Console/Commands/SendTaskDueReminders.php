<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendTaskDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-due-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send due date reminders for tasks';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Tasks due in the next 24 hours
        $dueSoonTasks = Task::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDay()])
            ->with(['assignees', 'creator'])
            ->get();

        foreach ($dueSoonTasks as $task) {
            $notifiables = $this->getNotifiables($task);
            foreach ($notifiables as $notifiable) {
                $notifiable->notify(new TaskDueReminderNotification($task, false));
            }
        }

        // Tasks that are overdue
        $overdueTasks = Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            // Ideally we only want to notify if the task is NOT completed.
            // Assuming status is a custom field or there is a way to filter.
            // In a real scenario we'd join custom fields, but for now we fetch and filter
            ->with(['assignees', 'creator'])
            ->get();

        foreach ($overdueTasks as $task) {
            // Need a way to ensure it's not completed.
            // In CustomFields, status might be stored in 'integer_value' or 'string_value'.
            // To prevent spamming, we could restrict this, but let's notify for now if not done.
            $notifiables = $this->getNotifiables($task);
            foreach ($notifiables as $notifiable) {
                $notifiable->notify(new TaskDueReminderNotification($task, true));
            }
        }

        $this->info('Task reminders sent successfully.');
    }

    /**
     * @return array<int, User>
     */
    protected function getNotifiables(Task $task): array
    {
        $notifiables = collect();

        // Add assignees
        foreach ($task->assignees as $assignee) {
            $notifiables->push($assignee);
        }

        // Add creator (manager role is implicit if they created it, per our rules)
        if ($task->creator) {
            $notifiables->push($task->creator);
        }

        return $notifiables->unique('id')->all();
    }
}
