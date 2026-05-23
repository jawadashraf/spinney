<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\EnquiryDirection;
use App\Enums\EnquiryOutcome;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CloseEnquiryAction
{
    public static function make(): Action
    {
        return Action::make('closeEnquiry')
            ->label('Close Enquiry')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->modalHeading('Close Enquiry')
            ->modalDescription('This will mark the enquiry as closed. No further changes will be possible.')
            ->schema([
                Select::make('outcome')
                    ->options(EnquiryOutcome::class)
                    ->native(false)
                    ->label('Call Outcome')
                    ->visible(fn (Enquiry $record): bool => $record->direction === EnquiryDirection::OUTBOUND),

                Textarea::make('closure_notes')
                    ->label('Closure Notes')
                    ->placeholder('Optional: Add any final notes about this enquiry...')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (array $data, Enquiry $record): void {
                $updateData = [
                    'status' => EnquiryStatus::CLOSED,
                ];

                if ($record->direction === EnquiryDirection::OUTBOUND && isset($data['outcome']) && $data['outcome']) {
                    $updateData['outcome'] = $data['outcome'];
                }

                $record->update($updateData);

                Notification::make()
                    ->title('Enquiry closed')
                    ->success()
                    ->send();
            });
    }
}
