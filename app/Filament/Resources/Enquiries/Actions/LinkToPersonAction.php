<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

final class LinkToPersonAction
{
    public static function make(): Action
    {
        return Action::make('linkToPerson')
            ->label('Link to Person')
            ->icon('heroicon-o-link')
            ->color('warning')
            ->modalHeading('Link Caller to Person Record')
            ->modalDescription('Associate this anonymous enquiry with an existing person in the system.')
            ->schema([
                Select::make('people_id')
                    ->relationship('people', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('phone')
                            ->tel(),
                        TextInput::make('email')
                            ->email(),
                    ])
                    ->createOptionModalHeading('Add New Person')
                    ->label('Select or Create Person')
                    ->required(),
            ])
            ->action(function (array $data, Enquiry $record): void {
                $record->update(['people_id' => $data['people_id']]);

                if ($record->people && empty($record->phone) && $record->people->phone) {
                    $record->update(['phone' => $record->people->phone]);
                }

                Notification::make()
                    ->title('Enquiry linked to '.$record->fresh()->people?->name)
                    ->success()
                    ->send();
            });
    }
}
