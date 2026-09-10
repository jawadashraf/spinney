<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\RescheduleCall;
use App\Models\Call;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class RescheduleCallsBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('rescheduleSelected')
            ->label('Change due dates')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->visible(fn (): bool => (bool) auth()->user()?->can('reassignAny', Call::class))
            ->authorizeIndividualRecords('reschedule')
            ->schema([
                Radio::make('mode')
                    ->options([
                        'shift' => 'Move by a number of days',
                        'set' => 'Set the same date for all',
                    ])
                    ->default('shift')
                    ->required()
                    ->live(),
                TextInput::make('shift_days')
                    ->label('Days (negative moves earlier)')
                    ->integer()
                    ->minValue(-30)
                    ->maxValue(90)
                    ->default(1)
                    ->required()
                    ->visible(fn (Get $get): bool => $get('mode') === 'shift'),
                DateTimePicker::make('due_at')
                    ->label('New due date')
                    ->seconds(false)
                    ->required()
                    ->visible(fn (Get $get): bool => $get('mode') === 'set'),
                Textarea::make('reason')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (Collection $records, array $data): void {
                $rescheduleCall = app(RescheduleCall::class);
                $count = 0;

                /** @var Call $call */
                foreach ($records as $call) {
                    if (! $call->isOpen()) {
                        continue;
                    }

                    $dueAt = $data['mode'] === 'set'
                        ? Carbon::parse($data['due_at'])
                        : $call->due_at->copy()->addDays((int) $data['shift_days']);

                    $rescheduleCall->handle($call, $dueAt, $data['reason'] ?? null);
                    $count++;
                }

                Notification::make()
                    ->title("{$count} ".str('call')->plural($count).' rescheduled')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
