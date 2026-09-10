<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Models\Call;
use App\Models\CallPlan;
use App\Models\Enquiry;
use App\Notifications\CallAssignedNotification;
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
            ->modalHeading(fn (Enquiry $record): string => self::isServiceUser($record) ? 'Schedule Follow-up Call' : 'Create Follow-up Enquiry')
            ->modalDescription(fn (Enquiry $record): string => self::isServiceUser($record)
                ? 'This will schedule a liaison call for this service user.'
                : 'This will create a new outbound follow-up enquiry linked to this record.')
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
                    ->label('Call Type')
                    ->hidden(fn (Enquiry $record): bool => self::isServiceUser($record)),

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

                Select::make('assigned_user_id')
                    ->label('Assign liaison')
                    ->options(fn (): array => CallResource::assignableUserOptions())
                    ->searchable()
                    ->visible(fn (Enquiry $record): bool => self::isServiceUser($record)),

                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Assign to Department')
                    ->placeholder('Unassigned'),
            ])
            ->action(function (array $data, Enquiry $record): void {
                if (self::isServiceUser($record)) {
                    self::scheduleCall($data, $record);

                    return;
                }

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

    private static function isServiceUser(Enquiry $record): bool
    {
        $person = $record->people;

        return $person !== null && ($person->is_service_user || $person->getAttribute('type') === 'service_user');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function scheduleCall(array $data, Enquiry $record): void
    {
        $call = Call::create([
            'team_id' => $record->team_id,
            'people_id' => $record->people_id,
            'call_plan_id' => CallPlan::query()->where('people_id', $record->people_id)->where('is_active', true)->value('id'),
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'department_id' => $data['department_id'] ?? $record->department_id,
            'enquiry_id' => $record->id,
            'reason' => $data['reason_for_contact'],
            'due_at' => $data['due_date'],
        ]);

        if ($call->assignee !== null && $call->assigned_user_id !== auth()->id()) {
            $call->assignee->notify(new CallAssignedNotification($call));
        }

        Notification::make()
            ->title('Follow-up call scheduled')
            ->success()
            ->send();

        redirect(CallResource::getUrl('view', ['record' => $call]));
    }
}
