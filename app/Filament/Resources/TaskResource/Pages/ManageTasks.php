<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\TaskResource;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskPriorityChangedNotification;
use App\Support\CustomFields\Concerns\InteractsWithCustomFields;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Size;

final class ManageTasks extends ManageRecords
{
    use InteractsWithCustomFields;
    use SyncsPermissionTeamId;

    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus')->size(Size::Small)->slideOver()
                ->after(function (Task $record) {
                    foreach ($record->assignees as $assignee) {
                        $assignee->notify(new TaskAssignedNotification($record));
                    }

                    $customFields = CustomField::where('entity_type', Task::class)->get();
                    $priorityField = $customFields->firstWhere('code', 'priority');
                    if ($priorityField) {
                        $newPriority = $record->getCustomFieldValue($priorityField);
                        $option = CustomFieldOption::find($newPriority);
                        if ($option && strtolower($option->name) === 'urgent') {
                            $notifiables = collect($record->assignees)->push($record->creator)->filter()->unique('id');
                            foreach ($notifiables as $user) {
                                $user->notify(new TaskPriorityChangedNotification($record));
                            }
                        }
                    }
                }),
        ];
    }
}
