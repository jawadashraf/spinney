<?php

declare(strict_types=1);

namespace App\Filament\Resources\Schedules\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

final class ViewBookableSlotsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Find Available Slots')
            ->icon('heroicon-o-calendar-days')
            ->color('info')
            ->modalHeading('Find Available Slots')
            ->modalWidth('lg')
            ->form([
                Select::make('counselor_id')
                    ->label('Counselor')
                    ->options(fn () => User::whereHas('roles', fn ($q) => $q->whereNot('name', 'service_user'))->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->native(false)
                    ->minDate(now()->toDateString()),
                Select::make('slot_duration')
                    ->label('Slot Duration')
                    ->options([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '60 min'])
                    ->default(60)
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $counselor = User::find($data['counselor_id']);
                if (! $counselor || ! method_exists($counselor, 'getBookableSlots')) {
                    return;
                }

                $slots = $counselor->getBookableSlots($data['date'], (int) $data['slot_duration']);

                $this->sendSuccessNotification(
                    'Found '.count(array_filter($slots, fn ($s) => $s['is_available'])).' available slots out of '.count($slots).' total.'
                );
            });
    }
}
