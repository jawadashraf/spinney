<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Schemas;

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Caller & Enquiry Details')
                    ->icon(Heroicon::User)
                    ->schema([
                        self::getPeopleIdField(),
                        self::getCallerNoteFieldForEdit(),
                        self::getPhoneFieldForEdit(),
                        self::getDirectionField(),
                        self::getCallTypeFieldForEdit(),
                        self::getSourceField(),
                        self::getCategoryField(),
                        self::getOccurredAtField(),
                        self::getReasonForContactField(),
                    ])
                    ->columns(2),

                Section::make('Assignment & Follow-up')
                    ->icon(Heroicon::UserGroup)
                    ->schema([
                        self::getDepartmentIdField(),
                        self::getDueDateFieldForEdit(),
                        self::getUserIdField(),
                    ])
                    ->columns(2),

                Section::make('Safeguarding & Risk')
                    ->icon(Heroicon::ShieldExclamation)
                    ->schema([
                        self::getSafeguardingFlagsField(),
                        self::getRiskFlagsFieldForEdit(),
                    ])
                    ->columns(1),

                Section::make('Actions & Referrals')
                    ->icon(Heroicon::ClipboardDocumentCheck)
                    ->schema([
                        self::getAdviceGivenField(),
                        self::getActionTakenField(),
                        self::getReferralTypeField(),
                        self::getReferralDestinationFieldForEdit(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getWizardSteps(): array
    {
        return [
            Step::make('Caller Identity')
                ->icon(Heroicon::User)
                ->description('Who is this enquiry from?')
                ->schema([
                    self::getCallerIdentificationModeField(),
                    self::getPeopleIdField(),
                    self::getCallerNoteField(),
                    self::getPhoneField(),
                    self::getDirectionField(),
                    self::getCallTypeField(),
                    self::getSourceField(),
                    self::getCategoryField(),
                    self::getOccurredAtField(),
                ])
                ->columns(2),

            Step::make('Details & Assignment')
                ->icon(Heroicon::ClipboardDocumentCheck)
                ->description('What is this about and who handles it?')
                ->schema([
                    self::getReasonForContactField(),
                    self::getDepartmentIdField(),
                    self::getDueDateField(),
                    self::getSafeguardingFlagsField(),
                    self::getRiskFlagsField(),
                ])
                ->columns(1),

            Step::make('Actions & Outcomes')
                ->icon(Heroicon::CheckCircle)
                ->description('What was done? (optional)')
                ->schema([
                    self::getAdviceGivenField(),
                    self::getActionTakenField(),
                    self::getReferralTypeField(),
                    self::getReferralDestinationField(),
                    self::getUserIdField(),
                ])
                ->columns(2),
        ];
    }

    public static function getCallerIdentificationModeField(): ToggleButtons
    {
        return ToggleButtons::make('caller_identification_mode')
            ->label('Caller Type')
            ->options([
                'known' => 'Known Caller',
                'new' => 'New Caller',
                'anonymous' => 'Anonymous / Withheld',
            ])
            ->inline()
            ->default('known')
            ->live()
            ->grouped()
            ->colors([
                'known' => 'primary',
                'new' => 'info',
                'anonymous' => 'gray',
            ])
            ->saved(false);
    }

    public static function getPeopleIdField(): Select
    {
        return Select::make('people_id')
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
            ->createOptionModalHeading('Add New Caller')
            ->visible(fn (Get $get): bool => in_array($get('caller_identification_mode'), ['known', 'new', null]))
            ->label('Caller')
            ->columnSpanFull();
    }

    public static function getCallerNoteField(): Textarea
    {
        return Textarea::make('caller_note')
            ->rows(2)
            ->label('Caller Notes')
            ->placeholder('Walk-in, male, approx 30s, Bangladeshi accent...')
            ->helperText('Brief description for anonymous callers. Not stored as personal data.')
            ->maxLength(500)
            ->visible(fn (Get $get): bool => $get('caller_identification_mode') === 'anonymous');
    }

    private static function getCallerNoteFieldForEdit(): Textarea
    {
        return Textarea::make('caller_note')
            ->rows(2)
            ->label('Caller Notes (Anonymous)')
            ->placeholder('Walk-in, male, approx 30s, Bangladeshi accent...')
            ->helperText('Brief description for anonymous callers.')
            ->maxLength(500)
            ->visible(fn ($record): bool => $record?->people_id === null);
    }

    public static function getPhoneField(): TextInput
    {
        return TextInput::make('phone')
            ->tel()
            ->label('Phone Number')
            ->visible(fn (Get $get): bool => in_array($get('caller_identification_mode'), ['known', 'new', null]));
    }

    private static function getPhoneFieldForEdit(): TextInput
    {
        return TextInput::make('phone')
            ->tel()
            ->label('Phone Number');
    }

    public static function getDirectionField(): ToggleButtons
    {
        return ToggleButtons::make('direction')
            ->options(EnquiryDirection::class)
            ->inline()
            ->default('inbound')
            ->live()
            ->required();
    }

    public static function getCallTypeField(): Select
    {
        return Select::make('call_type')
            ->options(EnquiryCallType::class)
            ->native(false)
            ->default('general')
            ->required()
            ->live()
            ->visible(fn (Get $get): bool => $get('direction') === 'outbound' || filled($get('direction')));
    }

    private static function getCallTypeFieldForEdit(): Select
    {
        return Select::make('call_type')
            ->options(EnquiryCallType::class)
            ->native(false)
            ->default('general')
            ->required()
            ->live();
    }

    public static function getSourceField(): Select
    {
        return Select::make('source')
            ->options(EnquirySourceType::class)
            ->native(false)
            ->default('phone')
            ->required();
    }

    public static function getCategoryField(): Select
    {
        return Select::make('category')
            ->options(EnquiryCategory::class)
            ->native(false)
            ->required();
    }

    public static function getOccurredAtField(): DateTimePicker
    {
        return DateTimePicker::make('occurred_at')
            ->default(now())
            ->required()
            ->seconds(false)
            ->label('When did this happen?');
    }

    public static function getReasonForContactField(): Textarea
    {
        return Textarea::make('reason_for_contact')
            ->rows(4)
            ->columnSpanFull()
            ->required()
            ->maxLength(2000);
    }

    public static function getSafeguardingFlagsField(): Toggle
    {
        return Toggle::make('safeguarding_flags')
            ->onColor('danger')
            ->offColor('gray')
            ->onIcon('heroicon-m-exclamation-triangle')
            ->label('Safeguarding Flags')
            ->helperText('Toggle ON if safeguarding concerns are present.')
            ->live();
    }

    public static function getRiskFlagsField(): Textarea
    {
        return Textarea::make('risk_flags')
            ->rows(2)
            ->label('Risk Flags')
            ->placeholder('Describe any known risks...')
            ->maxLength(1000)
            ->visible(fn (Get $get): bool => (bool) $get('safeguarding_flags'));
    }

    private static function getRiskFlagsFieldForEdit(): Textarea
    {
        return Textarea::make('risk_flags')
            ->rows(2)
            ->label('Risk Flags')
            ->placeholder('Describe any known risks...')
            ->maxLength(1000);
    }

    public static function getAdviceGivenField(): Textarea
    {
        return Textarea::make('advice_given')
            ->rows(3)
            ->columnSpanFull()
            ->maxLength(2000);
    }

    public static function getActionTakenField(): Textarea
    {
        return Textarea::make('action_taken')
            ->rows(3)
            ->columnSpanFull()
            ->maxLength(2000);
    }

    public static function getReferralTypeField(): Radio
    {
        return Radio::make('referral_type')
            ->options([
                'internal' => 'Internal Referral',
                'external' => 'External Referral',
            ])
            ->inline()
            ->label('Referral Type');
    }

    public static function getReferralDestinationField(): TextInput
    {
        return TextInput::make('referral_destination')
            ->label('Referral Destination')
            ->visible(fn (Get $get): bool => filled($get('referral_type')))
            ->placeholder('e.g. Local Authority, NHS Trust...')
            ->maxLength(255);
    }

    private static function getReferralDestinationFieldForEdit(): TextInput
    {
        return TextInput::make('referral_destination')
            ->label('Referral Destination')
            ->visible(fn (Get $get): bool => filled($get('referral_type')))
            ->placeholder('e.g. Local Authority, NHS Trust...')
            ->maxLength(255);
    }

    public static function getUserIdField(): Select
    {
        return Select::make('user_id')
            ->relationship('user', 'name')
            ->default(fn () => auth()->user()?->id)
            ->required()
            ->label('Staff Member');
    }

    public static function getDepartmentIdField(): Select
    {
        return Select::make('department_id')
            ->relationship('department', 'name')
            ->searchable()
            ->preload()
            ->label('Assign to Department')
            ->placeholder('Unassigned');
    }

    public static function getDueDateField(): DateTimePicker
    {
        return DateTimePicker::make('due_date')
            ->label('Due Date')
            ->seconds(false)
            ->visible(fn (Get $get): bool => $get('direction') === 'outbound');
    }

    private static function getDueDateFieldForEdit(): DateTimePicker
    {
        return DateTimePicker::make('due_date')
            ->label('Due Date')
            ->seconds(false);
    }
}
