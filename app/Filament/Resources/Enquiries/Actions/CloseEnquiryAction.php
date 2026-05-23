<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

final class CloseEnquiryAction
{
    public static function make(): Action
    {
        return Action::make('closeEnquiry')
            ->label('Close Enquiry')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Close Enquiry')
            ->modalDescription('This will mark the enquiry as closed. No further changes will be possible.')
            ->schema([
                Textarea::make('closure_notes')
                    ->label('Closure Notes')
                    ->placeholder('Optional: Add any final notes about this enquiry...')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (array $data, Enquiry $record): void {
                $record->update([
                    'status' => EnquiryStatus::CLOSED,
                ]);

                Notification::make()
                    ->title('Enquiry closed')
                    ->success()
                    ->send();
            });
    }
}
