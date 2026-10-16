<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Actions\Appointments\BookAppointment;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Zap\Exceptions\InvalidScheduleException;
use Zap\Exceptions\ScheduleConflictException;

final class CreateAppointment extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data = AppointmentForm::mutateFormDataBeforeSave($data);

        $schedulableClass = Relation::getMorphedModel($data['schedulable_type']) ?? $data['schedulable_type'];
        $schedulable = $schedulableClass::findOrFail($data['schedulable_id']);
        $metadata = $data['metadata'] ?? [];

        try {
            return app(BookAppointment::class)->handle(
                schedulable: $schedulable,
                date: $data['start_date'],
                startTime: $metadata['start_time'],
                endTime: $metadata['end_time'],
                name: $data['name'] ?? '',
                metadata: $metadata,
                teamId: (int) ($data['team_id'] ?? Filament::getTenant()?->getKey() ?? auth()->user()?->current_team_id),
                description: $data['description'] ?? null,
                active: (bool) ($data['is_active'] ?? true),
            );
        } catch (ScheduleConflictException) {
            Notification::make()
                ->title('Schedule Conflict')
                ->body('This schedule conflicts with an existing schedule.')
                ->danger()
                ->send();

            throw new Halt;
        } catch (InvalidScheduleException $e) {
            Notification::make()
                ->title('Invalid Schedule')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
