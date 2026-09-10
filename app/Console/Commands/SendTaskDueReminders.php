<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CustomFields\TaskField;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
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
        $dueSoonTasks = $this->incompleteTasks()
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
        $overdueTasks = $this->incompleteTasks()
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->with(['assignees', 'creator'])
            ->get();

        foreach ($overdueTasks as $task) {
            $notifiables = $this->getNotifiables($task);
            foreach ($notifiables as $notifiable) {
                $notifiable->notify(new TaskDueReminderNotification($task, true));
            }
        }

        $this->info('Task reminders sent successfully.');
    }

    /**
     * Tasks whose status custom field is not set to "Done".
     *
     * @return Builder<Task>
     */
    private function incompleteTasks(): Builder
    {
        $statusFieldIds = CustomField::query()
            ->where('entity_type', Task::class)
            ->where('code', TaskField::STATUS->value)
            ->pluck('id');

        $doneOptionIds = CustomFieldOption::query()
            ->whereIn('custom_field_id', $statusFieldIds)
            ->where('name', 'Done')
            ->pluck('id');

        return Task::query()->when(
            $doneOptionIds->isNotEmpty(),
            fn (Builder $query): Builder => $query->whereDoesntHave(
                'customFieldValues',
                fn (Builder $values): Builder => $values
                    ->whereIn('custom_field_id', $statusFieldIds)
                    ->whereIn('integer_value', $doneOptionIds),
            ),
        );
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
