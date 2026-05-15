<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Models\Schedule;
use Filament\Actions\Action;

final class UnlockAvailabilityAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unlock')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Unlock Availability')
            ->modalDescription('This will allow the counselor to edit this availability schedule again.')
            ->visible(fn (Schedule $record): bool => ($record->schedule_type->value ?? '') === 'availability'
                && ($record->metadata['is_locked'] ?? false) === true)
            ->action(function (Schedule $record): void {
                $record->metadata = array_merge($record->metadata ?? [], ['is_locked' => false]);
                $record->save();
            });
    }
}
