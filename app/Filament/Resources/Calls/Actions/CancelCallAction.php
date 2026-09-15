<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\ScheduleCall;
use App\Enums\CallStatus;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CancelCallAction
{
    public static function make(): Action
    {
        return Action::make('cancelCall')
            ->label('Cancel call')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (Call $record): bool => $record->isOpen())
            ->authorize('delete')
            ->requiresConfirmation()
            ->modalDescription(fn (Call $record): string => $record->plan?->is_active === true
                ? 'The call will be cancelled and the next regular call from the plan will be booked.'
                : 'The call will be cancelled and no follow-up will be scheduled.')
            ->schema([
                Toggle::make('pause_plan')
                    ->label('Also pause this call plan')
                    ->helperText('No further calls are booked until the plan is switched back on.')
                    ->visible(fn (Call $record): bool => self::canPausePlan($record)),
            ])
            ->action(function (array $data, Call $record): void {
                $pausePlan = (bool) ($data['pause_plan'] ?? false) && self::canPausePlan($record);

                $record->update(['status' => CallStatus::Cancelled]);

                if ($pausePlan) {
                    $record->plan?->update(['is_active' => false]);
                }

                $nextCall = $pausePlan ? null : app(ScheduleCall::class)->nextFromPlan($record);

                Notification::make()
                    ->title('Call cancelled')
                    ->body(match (true) {
                        $pausePlan => 'The call plan has been paused.',
                        $nextCall !== null => 'Next call scheduled for '.$nextCall->due_at->format('d M Y, H:i').'.',
                        default => null,
                    })
                    ->success()
                    ->send();
            });
    }

    private static function canPausePlan(Call $record): bool
    {
        return $record->plan !== null
            && $record->plan->is_active
            && (bool) auth()->user()?->can('update', $record->plan);
    }
}
