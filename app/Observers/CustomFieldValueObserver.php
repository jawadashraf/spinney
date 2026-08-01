<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CustomFieldOption;
use App\Models\CustomFieldValue;
use App\Models\Task;
use Filament\Facades\Filament;

final readonly class CustomFieldValueObserver
{
    public function saved(CustomFieldValue $customFieldValue): void
    {
        $field = $customFieldValue->customField;
        $entity = $customFieldValue->entity;

        if (! $field || ! $entity) {
            return;
        }

        if ($field->code === 'status' && $entity instanceof Task) {
            $rawValue = $customFieldValue->getValue();
            $option = is_scalar($rawValue) ? CustomFieldOption::find($rawValue) : null;
            $statusName = $option ? $option->name : (string) $rawValue;

            $isDone = strtolower((string) $statusName) === 'done';

            $activity = activity()
                ->performedOn($entity)
                ->event($isDone ? 'completed' : 'updated');

            if (auth('web')->check()) {
                $activity->causedBy(auth('web')->user());
            }

            $tenant = Filament::getTenant();
            $teamId = $tenant ? $tenant->getKey() : $entity->team_id;

            $activity->withProperties([
                'team_id' => $teamId,
                'status' => $statusName,
            ]);

            if ($isDone) {
                $activity->log('Task marked as Done');
            } else {
                $activity->log("Task status updated to {$statusName}");
            }
        }
    }
}
