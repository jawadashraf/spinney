<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CustomFields\TaskField;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Enums\TaskType;
use App\Models\Call;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('calls:migrate-legacy-follow-ups {--dry-run : Report what would be migrated without saving anything}')]
#[Description('Convert open follow-up call tasks and outbound follow-up enquiries into liaison calls')]
final class MigrateLegacyFollowUpCalls extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        [$tasks, $callsFromTasks] = $this->migrateTasks();
        [$enquiries, $callsFromEnquiries] = $this->migrateEnquiries();

        $dryRun ? DB::rollBack() : DB::commit();

        $this->table(['Source', 'Records migrated', 'Calls created'], [
            ['Follow-up call tasks', $tasks, $callsFromTasks],
            ['Outbound enquiries', $enquiries, $callsFromEnquiries],
        ]);

        if ($dryRun) {
            $this->warn('Dry run – no changes were saved.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{int, int}
     */
    private function migrateTasks(): array
    {
        $migrated = 0;
        $created = 0;

        Task::query()
            ->where('type', TaskType::FollowUpCall)
            ->whereNotIn('id', Call::withTrashed()->whereNotNull('task_id')->select('task_id'))
            ->with(['people', 'assignees'])
            ->get()
            ->each(function (Task $task) use (&$migrated, &$created): void {
                $statusField = $this->taskStatusField($task);
                $doneOptionId = $statusField ? $this->doneOptionId($statusField) : null;

                if ($statusField && $doneOptionId && (string) $task->getCustomFieldValue($statusField) === (string) $doneOptionId) {
                    return;
                }

                $serviceUsers = $task->people->filter(fn (People $person): bool => $this->isServiceUser($person));

                if ($serviceUsers->isEmpty()) {
                    return;
                }

                foreach ($serviceUsers as $person) {
                    Call::create([
                        'team_id' => $task->team_id,
                        'people_id' => $person->id,
                        'assigned_user_id' => $task->assignees->first()?->id,
                        'department_id' => $task->department_id,
                        'task_id' => $task->id,
                        'reason' => $task->title,
                        'due_at' => $task->due_date ?? now(),
                        'creator_id' => $task->creator_id,
                    ]);
                    $created++;
                }

                if ($statusField && $doneOptionId) {
                    $task->saveCustomFieldValue($statusField, (string) $doneOptionId);
                }

                $migrated++;
            });

        return [$migrated, $created];
    }

    /**
     * @return array{int, int}
     */
    private function migrateEnquiries(): array
    {
        $migrated = 0;

        Enquiry::query()
            ->where('direction', EnquiryDirection::OUTBOUND)
            ->whereIn('status', [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS])
            ->whereNotNull('people_id')
            ->whereNotIn('id', Call::withTrashed()->whereNotNull('enquiry_id')->select('enquiry_id'))
            ->with('people')
            ->get()
            ->filter(fn (Enquiry $enquiry): bool => $enquiry->people !== null && $this->isServiceUser($enquiry->people))
            ->each(function (Enquiry $enquiry) use (&$migrated): void {
                Call::create([
                    'team_id' => $enquiry->team_id,
                    'people_id' => $enquiry->people_id,
                    'department_id' => $enquiry->department_id,
                    'enquiry_id' => $enquiry->id,
                    'reason' => $enquiry->reason_for_contact,
                    'due_at' => $enquiry->due_date ?? now(),
                    'creator_id' => $enquiry->user_id,
                ]);

                $enquiry->update(['status' => EnquiryStatus::CLOSED]);
                $migrated++;
            });

        return [$migrated, $migrated];
    }

    private function isServiceUser(People $person): bool
    {
        return $person->is_service_user || $person->getAttribute('type') === 'service_user';
    }

    private function taskStatusField(Task $task): ?CustomField
    {
        return CustomField::query()
            ->where('team_id', $task->team_id)
            ->where('entity_type', $task::class)
            ->where('code', TaskField::STATUS->value)
            ->first();
    }

    private function doneOptionId(CustomField $statusField): ?int
    {
        return CustomFieldOption::query()
            ->where('custom_field_id', $statusField->id)
            ->where('name', 'Done')
            ->value('id');
    }
}
