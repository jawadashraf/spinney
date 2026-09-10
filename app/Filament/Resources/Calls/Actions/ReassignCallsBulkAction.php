<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\ReassignCalls;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

final class ReassignCallsBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('reassignSelected')
            ->label('Assign liaison')
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(fn (): bool => (bool) auth()->user()?->can('reassignAny', Call::class))
            ->authorizeIndividualRecords('reassign')
            ->schema([
                Select::make('assigned_user_id')
                    ->label('Liaison')
                    ->options(fn (): array => CallResource::assignableUserOptions())
                    ->searchable()
                    ->required(),
            ])
            ->action(
                function (Collection $records, array $data): void {
                    /** @var Collection<int, Call> $records */
                    $count = app(ReassignCalls::class)->handle($records, User::query()->findOrFail((int) $data['assigned_user_id']));

                    Notification::make()
                        ->title("{$count} ".str('call')->plural($count).' reassigned')
                        ->success()
                        ->send();
                }
            )
            ->deselectRecordsAfterCompletion();
    }
}
