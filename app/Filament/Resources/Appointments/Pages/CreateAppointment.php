<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Enums\ScheduleType;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Models\Schedule;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Zap\Exceptions\InvalidScheduleException;
use Zap\Exceptions\ScheduleConflictException;
use Zap\Facades\Zap;

final class CreateAppointment extends CreateRecord
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data = AppointmentForm::mutateFormDataBeforeSave($data);

        $schedulableClass = Relation::getMorphedModel($data['schedulable_type']) ?? $data['schedulable_type'];
        $schedulable = $schedulableClass::findOrFail($data['schedulable_id']);

        $scheduleType = ScheduleType::APPOINTMENT;
        $metadata = $data['metadata'] ?? [];

        unset($data['schedulable_type'], $data['schedulable_id'], $data['metadata'], $data['period_start_time'], $data['period_end_time']);

        if (! empty($metadata)) {
            $metadata = array_filter($metadata, fn ($value): bool => $value !== null && $value !== '');
        }

        $builder = Zap::for($schedulable)
            ->named($data['name'] ?? '')
            ->from($data['start_date']);

        if (! empty($data['end_date'])) {
            $builder->to($data['end_date']);
        }

        if (! empty($data['description'])) {
            $builder->description($data['description']);
        }

        $builder->appointment();

        if ($data['is_active'] ?? true) {
            $builder->active();
        } else {
            $builder->inactive();
        }

        if (! empty($metadata)) {
            $builder->withMetadata($metadata);
        }

        $teamId = $data['team_id'] ?? auth()->user()?->current_team_id;
        unset($data['team_id']);

        $periodStartTime = request()->input('period_start_time') ?? request()->input('data.period_start_time') ?? '09:00';
        $periodEndTime = request()->input('period_end_time') ?? request()->input('data.period_end_time') ?? '17:00';

        if ($periodStartTime && $periodEndTime) {
            $builder->addPeriod($periodStartTime, $periodEndTime);
        }

        try {
            $schedule = $builder->save();

            if ($teamId) {
                $schedule->team_id = $teamId;
                $schedule->save();
            }

            return $schedule;
        } catch (ScheduleConflictException $e) {
            Notification::make()
                ->title('Schedule Conflict')
                ->body('This schedule conflicts with an existing schedule.')
                ->danger()
                ->send();

            return new Schedule;
        } catch (InvalidScheduleException $e) {
            Notification::make()
                ->title('Invalid Schedule')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return new Schedule;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
