<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Models\Schedule;
use Filament\Actions\Action;

final class LockAvailabilityAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Lock')
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Lock Availability')
            ->modalDescription('This will prevent the counselor from editing this availability schedule. Only managers and admins can unlock it.')
            ->visible(fn (Schedule $record): bool => ($record->schedule_type->value ?? '') === 'availability'
                && ! ($record->metadata['is_locked'] ?? false)
                && $record->is_active)
            ->action(function (Schedule $record): void {
                $record->metadata = array_merge($record->metadata ?? [], ['is_locked' => true]);
                $record->save();
            });
    }
}
