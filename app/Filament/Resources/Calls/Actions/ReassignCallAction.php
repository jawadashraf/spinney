<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\ReassignCalls;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class ReassignCallAction
{
    public static function make(): Action
    {
        return Action::make('reassign')
            ->label('Assign liaison')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->visible(fn (Call $record): bool => $record->isOpen())
            ->authorize('reassign')
            ->modalHeading('Assign liaison')
            ->fillForm(fn (Call $record): array => ['assigned_user_id' => $record->assigned_user_id])
            ->schema([
                Select::make('assigned_user_id')
                    ->label('Liaison')
                    ->options(fn (): array => CallResource::assignableUserOptions())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Call $record): void {
                app(ReassignCalls::class)->handle([$record], User::query()->findOrFail((int) $data['assigned_user_id']));

                Notification::make()
                    ->title('Call reassigned')
                    ->success()
                    ->send();
            });
    }
}
