<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CreateFollowUpAction
{
    public static function make(): Action
    {
        return Action::make('createFollowUp')
            ->label('Create Follow-up')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->modalHeading('Create Follow-up Enquiry')
            ->modalDescription('This will create a new outbound follow-up enquiry linked to this record.')
            ->schema([
                Select::make('call_type')
                    ->options([
                        EnquiryCallType::FOLLOW_UP->value => EnquiryCallType::FOLLOW_UP->getLabel(),
                        EnquiryCallType::CHECK_IN->value => EnquiryCallType::CHECK_IN->getLabel(),
                        EnquiryCallType::SCHEDULED->value => EnquiryCallType::SCHEDULED->getLabel(),
                        EnquiryCallType::EMERGENCY->value => EnquiryCallType::EMERGENCY->getLabel(),
                    ])
                    ->default(EnquiryCallType::FOLLOW_UP->value)
                    ->native(false)
                    ->required()
                    ->label('Call Type'),

                DateTimePicker::make('due_date')
                    ->label('Due Date')
                    ->seconds(false)
                    ->minDate(now())
                    ->required(),

                Textarea::make('reason_for_contact')
                    ->rows(3)
                    ->required()
                    ->maxLength(2000)
                    ->label('Reason for Follow-up'),

                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Assign to Department')
                    ->placeholder('Unassigned'),
            ])
            ->action(function (array $data, Enquiry $record): void {
                $callType = EnquiryCallType::from($data['call_type']);

                $followUp = Enquiry::create([
                    'direction' => EnquiryDirection::OUTBOUND,
                    'call_type' => $callType,
                    'source' => $record->source ?? EnquirySourceType::PHONE,
                    'people_id' => $record->people_id,
                    'phone' => $record->phone,
                    'category' => $record->category,
                    'reason_for_contact' => $data['reason_for_contact'],
                    'safeguarding_flags' => $callType === EnquiryCallType::EMERGENCY ? true : $record->safeguarding_flags,
                    'risk_flags' => $record->risk_flags,
                    'team_id' => $record->team_id,
                    'user_id' => auth()->id(),
                    'creator_id' => auth()->id(),
                    'status' => EnquiryStatus::OPEN,
                    'occurred_at' => now(),
                    'due_date' => $data['due_date'],
                    'department_id' => $data['department_id'] ?? $record->department_id,
                    'parent_enquiry_id' => $record->id,
                    'caller_type' => $record->people_id
                        ? CallerType::SERVICE_USER->value
                        : CallerType::ANONYMOUS->value,
                ]);

                Notification::make()
                    ->title('Follow-up enquiry created')
                    ->success()
                    ->send();

                redirect(EnquiryResource::getUrl('view', ['record' => $followUp]));
            });
    }
}
