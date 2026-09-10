<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\RescheduleCall;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

final class RescheduleCallAction
{
    public static function make(): Action
    {
        return Action::make('reschedule')
            ->label('Change due date')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('warning')
            ->visible(fn (Call $record): bool => $record->isOpen())
            ->authorize('reschedule')
            ->modalHeading('Change due date')
            ->fillForm(fn (Call $record): array => ['due_at' => $record->due_at->toDateTimeString()])
            ->schema([
                DateTimePicker::make('due_at')
                    ->label('New due date')
                    ->seconds(false)
                    ->required(),
                Textarea::make('reason')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (array $data, Call $record): void {
                app(RescheduleCall::class)->handle($record, Carbon::parse($data['due_at']), $data['reason'] ?? null);

                Notification::make()
                    ->title('Call rescheduled')
                    ->success()
                    ->send();
            });
    }
}
