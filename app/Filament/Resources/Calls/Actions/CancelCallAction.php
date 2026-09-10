<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Enums\CallStatus;
use App\Models\Call;
use Filament\Actions\Action;
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
            ->modalDescription('The call will be cancelled and no follow-up will be scheduled.')
            ->action(function (Call $record): void {
                $record->update(['status' => CallStatus::Cancelled]);

                Notification::make()
                    ->title('Call cancelled')
                    ->success()
                    ->send();
            });
    }
}
