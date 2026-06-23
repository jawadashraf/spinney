<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Schemas;

use App\Enums\EngagementStatus;
use App\Enums\Ethnicity;
use App\Enums\InjectionHistory;
use App\Enums\ReferralType;
use App\Enums\ServiceTeam;
use App\Enums\SubstanceUseFrequency;
use App\Enums\TreatmentOutcome;
use App\Models\Enquiry;
use App\Models\ServiceUser;
use App\Services\AddressLookupService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class ServiceUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    public static function getComponents(string $profilePrefix = 'profile.'): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make('Identity')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(
                                    table: 'users',
                                    column: 'email',
                                    modifyRuleUsing: function (Unique $rule, ?Model $record) {
                                        // If we are editing an existing ServiceUser, ignore its linked User's ID
                                        if ($record && $record->user_id) {
                                            return Rule::unique('users', 'email')->ignore($record->user_id, 'id');
                                        }

                                        return $rule;
                                    }
                                )
                                ->default(fn (?Model $record) => $record instanceof Enquiry ? $record->people?->email : null),
                            TextInput::make('password')
                                ->password()
                                ->helperText('Leave blank to auto-generate a secure password if creating.')
                                ->dehydrated(false)
                                ->visible(fn (string $context): bool => $context === 'create'),
                        ])->columns(2)->collapsible(),

                    Section::make('Service User Details')
                        ->schema([
                            Tabs::make('Profile Information')
                                ->contained(false)
                                ->tabs([
                                    Tab::make('Demographics & Consent')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            Section::make('Demographics')
                                                ->schema([
                                                    DatePicker::make('date_of_birth'),
                                                    Select::make('gender')
                                                        ->options([
                                                            'male' => 'Male',
                                                            'female' => 'Female',
                                                            'other' => 'Other',
                                                        ]),
                                                    Radio::make('ethnicity')
                                                        ->options(Ethnicity::class)
                                                        ->columns(3)
                                                        ->columnSpanFull()
                                                        ->live(),
                                                    TextInput::make('ethnicity_other')
                                                        ->label('Other ethnicity (please specify)')
                                                        ->visible(fn ($get): bool => $get('ethnicity') === 'other' ||
                                                            $get('ethnicity') === Ethnicity::Other ||
                                                            ($get('ethnicity') instanceof Ethnicity && $get('ethnicity')->value === 'other') ||
                                                            (is_array($get('ethnicity')) && in_array('other', $get('ethnicity'), true)) ||
                                                            (is_string($get('ethnicity')) && in_array('other', json_decode($get('ethnicity'), true) ?? [], true))
                                                        )
                                                        ->columnSpanFull(),
                                                    PhoneInput::make('phone')
                                                        ->initialCountry('gb'),
                                                    TextInput::make('postcode')
                                                        ->live(onBlur: true)
                                                        ->suffixAction(
                                                            Action::make('findAddress')
                                                                ->label('Find Address')
                                                                ->icon('heroicon-o-magnifying-glass')
                                                                ->color('primary')
                                                                ->modalHeading('Select Address')
                                                                ->modalWidth(Width::Medium)
                                                                ->form(fn (Get $get) => [
                                                                    Select::make('selected_address')
                                                                        ->label('Matching Addresses')
                                                                        ->placeholder('Select an address')
                                                                        ->options(function () use ($get) {
                                                                            $postcode = $get('postcode');
                                                                            if (empty($postcode)) {
                                                                                return [];
                                                                            }

                                                                            return resolve(AddressLookupService::class)->lookup($postcode);
                                                                        })
                                                                        ->required()
                                                                        ->searchable(),
                                                                ])
                                                                ->action(function (array $data, Set $set) {
                                                                    if (isset($data['selected_address'])) {
                                                                        $set('address', $data['selected_address']);
                                                                    }
                                                                })
                                                        ),
                                                    Toggle::make('no_fixed_address')
                                                        ->label('No current fixed address'),
                                                    Textarea::make('address')
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                    TextInput::make('availability')
                                                        ->placeholder('e.g. Weekdays after 5pm'),
                                                ])->columns(3),

                                            Section::make('Emergency Contact')
                                                ->schema([
                                                    TextInput::make('emergency_contact_name'),
                                                    PhoneInput::make('emergency_contact_number')
                                                        ->initialCountry('gb'),
                                                ])->columns(2),

                                            Section::make('Consent & GDPR')
                                                ->schema([
                                                    Toggle::make('consent_data_storage')
                                                        ->label('Consent for Data Storage')
                                                        ->required(),
                                                    Toggle::make('consent_referrals')
                                                        ->label('Consent for Referrals'),
                                                    Toggle::make('consent_communications')
                                                        ->label('Consent for Communications'),
                                                ])->columns(3),
                                        ]),

                                    Tab::make('Assessment')
                                        ->icon('heroicon-o-clipboard-document-list')
                                        ->schema([
                                            Section::make('Substance Use')
                                                ->schema([
                                                    CheckboxList::make("{$profilePrefix}addictions")
                                                        ->options([
                                                            'smoking' => 'Smoking',
                                                            'drugs' => 'Drugs',
                                                            'gambling' => 'Gambling',
                                                            'compulsive_behavior' => 'Compulsive Behavior',
                                                            'pornography' => 'Pornography',
                                                        ])
                                                        ->columns(3),
                                                    CheckboxList::make("{$profilePrefix}substances_used")
                                                        ->options([
                                                            'heroin' => 'Heroin',
                                                            'cocaine' => 'Cocaine',
                                                            'ketamine' => 'Ketamine',
                                                            'marijuana' => 'Marijuana',
                                                            'lsd' => 'LSD',
                                                            'ecstasy' => 'Ecstasy',
                                                            'spirits' => 'Spirits',
                                                            'wine' => 'Wine',
                                                            'beer' => 'Beer',
                                                        ])
                                                        ->columns(3),
                                                    Select::make("{$profilePrefix}frequency_of_use")
                                                        ->options(SubstanceUseFrequency::class),
                                                    TextInput::make("{$profilePrefix}amount_of_use"),
                                                    CheckboxList::make("{$profilePrefix}route_of_use")
                                                        ->options([
                                                            'smoke' => 'Smoke',
                                                            'sniff' => 'Sniff',
                                                            'oral' => 'Oral',
                                                            'inject' => 'Inject',
                                                        ])
                                                        ->columns(4),
                                                    TextInput::make("{$profilePrefix}age_first_used"),
                                                    Toggle::make("{$profilePrefix}overdosed_last_month"),
                                                    Radio::make("{$profilePrefix}injection_history")
                                                        ->options(InjectionHistory::class),
                                                ]),

                                            Section::make('GP & Health')
                                                ->schema([
                                                    Toggle::make("{$profilePrefix}registered_with_gp")
                                                        ->live(),
                                                    TextInput::make("{$profilePrefix}gp_name")
                                                        ->visible(fn ($get) => $get("{$profilePrefix}registered_with_gp") ?? false),
                                                    Textarea::make("{$profilePrefix}gp_address")
                                                        ->rows(2)
                                                        ->visible(fn ($get) => $get("{$profilePrefix}registered_with_gp") ?? false)
                                                        ->columnSpanFull(),
                                                ])->columns(2),
                                        ]),

                                    Tab::make('Referral')
                                        ->icon('heroicon-o-link')
                                        ->schema([
                                            Section::make('Referral Details')
                                                ->schema([
                                                    Radio::make("{$profilePrefix}referral_type")
                                                        ->options(ReferralType::class),
                                                    TextInput::make("{$profilePrefix}referral_source_specify")
                                                        ->label('Specify source'),
                                                    CheckboxList::make("{$profilePrefix}previous_input")
                                                        ->label('Previous Input')
                                                        ->options([
                                                            'gp' => 'GP',
                                                            'drug_agency' => 'Drug Agency',
                                                            'other' => 'Other',
                                                        ])
                                                        ->columns(3),
                                                    CheckboxList::make("{$profilePrefix}other_issues")
                                                        ->options([
                                                            'criminal_justice' => 'Criminal Justice',
                                                            'housing' => 'Housing',
                                                            'family' => 'Family',
                                                            'finance' => 'Finance',
                                                            'health' => 'Health',
                                                        ])
                                                        ->columns(3),
                                                    Textarea::make("{$profilePrefix}reason_for_referral")
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),

                                    Tab::make('Service Plan')
                                        ->icon('heroicon-o-briefcase')
                                        ->schema([
                                            Section::make('Service Assignment')
                                                ->schema([
                                                    Select::make("{$profilePrefix}target_service_team")
                                                        ->label('Service Team')
                                                        ->options(ServiceTeam::class)
                                                        ->native(false)
                                                        ->required(),
                                                    Select::make("{$profilePrefix}engagement_status")
                                                        ->options(EngagementStatus::class)
                                                        ->native(false)
                                                        ->default(EngagementStatus::ACTIVE->value)
                                                        ->required(),
                                                ])->columns(2),

                                            Section::make('Plan & Outcomes')
                                                ->schema([
                                                    CheckboxList::make("{$profilePrefix}referral_targets")
                                                        ->label('Next Steps (Referrals)')
                                                        ->options([
                                                            'spiritual' => 'Referral to Spiritual Team',
                                                            'turning_point' => 'Referral to Turning Point',
                                                            'alternative_therapy' => 'Referral to Alternative Therapy',
                                                            'family_support' => 'Referral to Family support',
                                                        ])
                                                        ->columns(2),
                                                    TextInput::make("{$profilePrefix}referral_agency_specify")
                                                        ->label('Specify Agency'),
                                                    CheckboxList::make("{$profilePrefix}intervention_offered")
                                                        ->options([
                                                            'quran' => 'Qur\'an class',
                                                            'group_therapy' => 'Group therapy',
                                                            'gym' => 'Gym',
                                                            'spiritual' => 'Spiritual',
                                                            'family_support' => 'Family support',
                                                        ])
                                                        ->columns(3),
                                                    Select::make("{$profilePrefix}treatment_outcome")
                                                        ->options(TreatmentOutcome::class),
                                                    Textarea::make("{$profilePrefix}internal_notes")
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),
                                ])->columnSpanFull(),
                        ])->collapsible(),

                ])
                ->columnSpanFull(),
        ];
    }
}
