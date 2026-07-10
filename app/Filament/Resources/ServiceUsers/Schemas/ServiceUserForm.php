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
use App\Filament\Resources\ServiceUsers\Pages\CreateServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\ServiceUser;
use App\Services\AddressLookupService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class ServiceUserForm
{
    public const string TAB_DEMOGRAPHICS_CONSENT = 'demographics-consent';

    public const string TAB_ASSESSMENT = 'assessment';

    public const string TAB_REFERRAL = 'referral';

    public const string TAB_SERVICE_PLAN = 'service-plan';

    /**
     * @var array<int, string>
     */
    public const array TABS = [
        self::TAB_DEMOGRAPHICS_CONSENT,
        self::TAB_ASSESSMENT,
        self::TAB_REFERRAL,
        self::TAB_SERVICE_PLAN,
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getComponents());
    }

    /**
     * @return array<int, Component>
     */
    public static function getComponents(string $profilePrefix = 'profile.'): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make('Identity')
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('last_name')
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
                                        /** @var ServiceUser|null $record */
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
                                ->livewireProperty('activeServiceUserTab')
                                ->tabs([
                                    self::TAB_DEMOGRAPHICS_CONSENT => Tab::make('Demographics & Consent')
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
                                                                ->form(fn (Get $get): array => [
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
                                                                ->action(function (array $data, Set $set): void {
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
                                                    Select::make('emergency_contact_id')
                                                        ->label('Emergency Contact')
                                                        ->options(fn (): array => People::query()
                                                            ->when(
                                                                Filament::getTenant(),
                                                                fn ($query, $tenant) => $query->where('team_id', $tenant->getKey()),
                                                            )
                                                            ->orderBy('name')
                                                            ->pluck('name', 'id')
                                                            ->all())
                                                        ->searchable()
                                                        ->preload()
                                                        ->native(false)
                                                        ->live()
                                                        ->nullable()
                                                        ->createOptionForm([
                                                            TextInput::make('first_name')
                                                                ->required()
                                                                ->maxLength(255),
                                                            TextInput::make('last_name')
                                                                ->required()
                                                                ->maxLength(255),
                                                            TextInput::make('email')
                                                                ->email()
                                                                ->maxLength(255),
                                                            TextInput::make('phone')
                                                                ->maxLength(255),
                                                        ])
                                                        ->createOptionModalHeading('Create emergency contact')
                                                        ->createOptionUsing(function (array $data): int {
                                                            $tenant = Filament::getTenant();

                                                            return People::create([
                                                                'team_id' => $tenant?->getKey(),
                                                                'first_name' => $data['first_name'],
                                                                'last_name' => $data['last_name'],
                                                                'email' => $data['email'] ?? null,
                                                                'phone' => $data['phone'] ?? null,
                                                            ])->getKey();
                                                        }),
                                                    Select::make('emergency_contact_relation_type')
                                                        ->label('Relationship')
                                                        ->options([
                                                            'mother' => 'Mother',
                                                            'father' => 'Father',
                                                            'spouse' => 'Spouse',
                                                            'partner' => 'Partner',
                                                            'sibling' => 'Sibling',
                                                            'child' => 'Child',
                                                            'friend' => 'Friend',
                                                            'other' => 'Other',
                                                        ])
                                                        ->visible(fn (Get $get): bool => filled($get('emergency_contact_id')))
                                                        ->required(fn (Get $get): bool => filled($get('emergency_contact_id')))
                                                        ->dehydrated(fn (Get $get): bool => filled($get('emergency_contact_id')))
                                                        ->nullable(),
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

                                    self::TAB_ASSESSMENT => Tab::make('Assessment')
                                        ->icon('heroicon-o-clipboard-document-list')
                                        ->schema([
                                            Section::make('Substance Use')
                                                ->schema([
                                                    CheckboxList::make("{$profilePrefix}addictions")
                                                        ->options([
                                                            'alcohol' => 'Alcohol',
                                                            'compulsive_behavior' => 'Compulsive Behavior',
                                                            'drugs' => 'Drugs',
                                                            'gambling' => 'Gambling',
                                                            'pornography' => 'Pornography',
                                                            'smoking' => 'Smoking',
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

                                    self::TAB_REFERRAL => Tab::make('Referral')
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

                                    self::TAB_SERVICE_PLAN => Tab::make('Service Plan')
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

                            Actions::make([
                                Action::make('previousTab')
                                    ->label('Back')
                                    ->icon('heroicon-m-arrow-left')
                                    ->color('gray')
                                    ->visible(fn (CreateServiceUser|EditServiceUser $livewire): bool => $livewire->activeServiceUserTab !== self::TAB_DEMOGRAPHICS_CONSENT)
                                    ->action(function (CreateServiceUser|EditServiceUser $livewire): void {
                                        $currentIndex = array_search($livewire->activeServiceUserTab, self::TABS, true);

                                        if ($currentIndex !== false && $currentIndex > 0) {
                                            $livewire->activeServiceUserTab = self::TABS[$currentIndex - 1];
                                        }
                                    }),
                                Action::make('nextTab')
                                    ->label('Next')
                                    ->icon('heroicon-m-arrow-right')
                                    ->iconPosition(IconPosition::After)
                                    ->visible(fn (CreateServiceUser|EditServiceUser $livewire): bool => $livewire->activeServiceUserTab !== self::TAB_SERVICE_PLAN)
                                    ->action(function (CreateServiceUser|EditServiceUser $livewire): void {
                                        $currentIndex = array_search($livewire->activeServiceUserTab, self::TABS, true);

                                        if ($currentIndex !== false && $currentIndex < count(self::TABS) - 1) {
                                            $livewire->activeServiceUserTab = self::TABS[$currentIndex + 1];
                                        }
                                    }),
                            ])
                                ->key('service-user-tab-navigation')
                                ->alignment(Alignment::Between)
                                ->columnSpanFull(),
                        ])->collapsible(),

                ])
                ->columnSpanFull(),
        ];
    }
}
