<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\ReassignCalls;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

final class CoverAbsenceAction
{
    public static function make(): Action
    {
        return Action::make('coverAbsence')
            ->label('Cover absence')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->visible(fn (): bool => (bool) auth()->user()?->can('reassignAny', Call::class))
            ->modalHeading('Move calls to another liaison')
            ->modalDescription('Moves open calls due in the chosen period, e.g. while a liaison is on leave.')
            ->schema([
                Select::make('from_user_id')
                    ->label('From liaison')
                    ->options(fn (): array => CallResource::assignableUserOptions())
                    ->searchable()
                    ->required(),
                Select::make('to_user_id')
                    ->label('To liaison')
                    ->options(fn (): array => CallResource::assignableUserOptions())
                    ->searchable()
                    ->required()
                    ->different('from_user_id'),
                DatePicker::make('from')
                    ->label('Calls due from')
                    ->default(today())
                    ->required(),
                DatePicker::make('until')
                    ->label('Calls due until')
                    ->required()
                    ->afterOrEqual('from'),
                Toggle::make('include_plans')
                    ->label('Also move their call plans permanently'),
            ])
            ->action(function (array $data): void {
                $calls = CallResource::getEloquentQuery()
                    ->open()
                    ->where('calls.assigned_user_id', $data['from_user_id'])
                    ->whereBetween('calls.due_at', [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['until'])->endOfDay()])
                    ->get();

                $to = User::query()->findOrFail((int) $data['to_user_id']);
                $count = app(ReassignCalls::class)->handle($calls, $to, (bool) ($data['include_plans'] ?? false));

                Notification::make()
                    ->title("{$count} ".str('call')->plural($count)." moved to {$to->name}")
                    ->success()
                    ->send();
            });
    }
}
