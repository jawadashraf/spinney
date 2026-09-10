<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Notifications\CallAssignedNotification;
use Filament\Resources\Pages\CreateRecord;

final class CreateCall extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = CallResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['original_due_at'] = $data['due_at'];

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Call $call */
        $call = $this->getRecord();

        if ($call->assignee !== null && $call->assigned_user_id !== auth()->id()) {
            $call->assignee->notify(new CallAssignedNotification($call));
        }
    }
}
