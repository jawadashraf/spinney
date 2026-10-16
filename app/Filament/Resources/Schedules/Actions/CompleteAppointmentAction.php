<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Actions\Appointments\UpdateAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Enums\AttendanceStatus;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Records how the session went: attended or no-show, a session note and the next step.
 */
final class CompleteAppointmentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Complete')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading('Record the session')
            ->modalSubmitActionLabel('Complete appointment')
            ->schema([
                Radio::make('attendance')
                    ->label('Did they attend?')
                    ->options([
                        AttendanceStatus::ATTENDED->value => 'Attended',
                        AttendanceStatus::NO_SHOW->value => 'Did not attend (no-show)',
                    ])
                    ->default(AttendanceStatus::ATTENDED->value)
                    ->required()
                    ->live(),
                Textarea::make('session_note')
                    ->label('Session note')
                    ->rows(5)
                    ->required(fn (Get $get): bool => $get('attendance') === AttendanceStatus::ATTENDED->value)
                    ->helperText('Only you, managers and admins can read this note.'),
                Textarea::make('next_step')
                    ->label('Next step')
                    ->rows(2)
                    ->placeholder('e.g. Book a follow-up in two weeks'),
            ])
            ->visible(fn (Schedule $record): bool => ($record->schedule_type->value ?? '') === 'appointment'
                && $record->getAppointmentStatus()->canTransitionTo(AppointmentStatus::COMPLETED))
            ->action(function (Schedule $record, array $data, UpdateAppointmentStatus $update): void {
                /** @var User $user */
                $user = auth()->user();
                $update->complete(
                    $record,
                    $user,
                    AttendanceStatus::from((string) $data['attendance']),
                    $data['session_note'] ?? null,
                    $data['next_step'] ?? null,
                );
            })
            ->successNotificationTitle('Session recorded');
    }
}
