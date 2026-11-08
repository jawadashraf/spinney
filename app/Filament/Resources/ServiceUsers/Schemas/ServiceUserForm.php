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
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\User;
use App\Services\AddressLookupService;
use App\Support\PhoneNumber;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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
    public static function getComponents(string $profilePrefix = 'profile.', bool $useRelationships = true, bool $useTabNavigation = true): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make('Identity')
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->default(fn (?Model $record) => $record instanceof Enquiry ? $record->people?->first_name : null),
                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->default(fn (?Model $record) => $record instanceof Enquiry ? $record->people?->last_name : null),
                            Toggle::make('has_no_email')
                                ->label('No email address available')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, ?bool $state): void {
                                    if ($state) {
                                        $domain = config('app.temp_email_domain', 'spinney.local');
                                        $set('email', 'temp_'.strtolower(Str::random(10)).'@'.$domain);
                                    } else {
                                        $set('email', null);
                                    }
                                })
                                ->afterStateHydrated(function (Toggle $component, ?Model $record): void {
                                    if (! $record instanceof Model) {
                                        return;
                                    }
                                    $domain = config('app.temp_email_domain', 'spinney.local');
                                    $hasNoEmail = str_contains((string) ($record->email ?? ''), '@'.$domain);
                                    $component->state($hasNoEmail);
                                })
                                ->default(fn (?Model $record): bool => $record && str_contains((string) ($record->email ?? ''), '@'.config('app.temp_email_domain', 'spinney.local')))
                                ->dehydrated(false),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
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
                                ->default(fn (?Model $record) => $record instanceof Enquiry ? $record->people?->email : null)
                                ->disabled(fn (Get $get): bool => (bool) $get('has_no_email'))
                                ->dehydrated()
                                ->helperText(fn (Get $get): ?string => $get('has_no_email') ? 'This temporary email was autogenerated because no email was available.' : null)
                                ->hint(fn (Get $get): ?string => $get('has_no_email') ? 'Autogenerated' : null)
                                ->hintIcon(fn (Get $get): ?string => $get('has_no_email') ? 'heroicon-m-information-circle' : null)
                                ->hintColor('warning'),
                            TextInput::make('password')
                                ->password()
                                ->helperText('Leave blank to auto-generate a secure password if creating.')
                                ->dehydrated(false)
                                ->visible(fn (string $context): bool => $context === 'create'),
                            Callout::make('duplicate_identity_warning')
                                ->columnSpanFull()
                                ->visible(fn (Get $get, ?Model $record): bool => self::detectIdentityDuplicate($get, $record) !== null)
                                ->heading(fn (Get $get, ?Model $record): ?string => self::detectIdentityDuplicate($get, $record)['heading'] ?? null)
                                ->color(fn (Get $get, ?Model $record): ?string => self::detectIdentityDuplicate($get, $record)['color'] ?? 'warning')
                                ->description(fn (Get $get, ?Model $record): ?HtmlString => self::detectIdentityDuplicate($get, $record)['description'] ?? null)
                                ->actions([
                                    Action::make('viewDuplicateServiceUser')
                                        ->label(fn (Get $get, ?Model $record): string => self::detectIdentityDuplicate($get, $record)['action_label'] ?? 'View Record')
                                        ->url(fn (Get $get, ?Model $record): ?string => self::detectIdentityDuplicate($get, $record)['action_url'] ?? null)
                                        ->visible(fn (Get $get, ?Model $record): bool => filled(self::detectIdentityDuplicate($get, $record)['action_url'] ?? null))
                                        ->openUrlInNewTab()
                                        ->color(fn (Get $get, ?Model $record): string => self::detectIdentityDuplicate($get, $record)['color'] ?? 'warning')
                                        ->icon('heroicon-m-arrow-top-right-on-square'),
                                ]),
                        ])->columns(2)->collapsible(),

                    Section::make('Service User Details')
                        ->schema([
                            Tabs::make('Profile Information')
                                ->contained(false)
                                ->when(
                                    $useTabNavigation,
                                    fn (Tabs $component): Tabs => $component->livewireProperty('activeServiceUserTab')
                                )
                                ->tabs([
                                    self::TAB_DEMOGRAPHICS_CONSENT => Tab::make('Demographics & Consent')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            Section::make('Demographics')
                                                ->schema([
                                                    DatePicker::make('date_of_birth')
                                                        ->live(),
                                                    Select::make('gender')
                                                        ->options([
                                                            'male' => 'Male',
                                                            'female' => 'Female',
                                                            'other' => 'Other',
                                                        ]),
                                                    Section::make('Ethnicity')
                                                        ->schema([
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
                                                        ])
                                                        ->collapsible()
                                                        ->collapsed()
                                                        ->columnSpanFull(),
                                                    PhoneInput::make('phone')
                                                        ->initialCountry('gb')
                                                        ->live(onBlur: true),
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
                                                                        ->options(function () use ($get): array {
                                                                            $postcode = (string) $get('postcode');
                                                                            if (trim($postcode) === '') {
                                                                                return [];
                                                                            }

                                                                            $detailed = resolve(AddressLookupService::class)->lookupDetailed($postcode);
                                                                            $options = [];

                                                                            foreach ($detailed as $item) {
                                                                                $formatted = (string) ($item['formatted'] ?? '');
                                                                                if ($formatted !== '') {
                                                                                    $options[$formatted] = str_replace("\n", ', ', $formatted);
                                                                                }
                                                                            }

                                                                            return $options;
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
                                                    Select::make('availability')
                                                        ->options([
                                                            'before_12' => 'Before 12',
                                                            'after_12' => 'After 12',
                                                            'anytime' => 'Anytime',
                                                        ])
                                                        ->native(false),
                                                    Callout::make('duplicate_demographics_warning')
                                                        ->columnSpanFull()
                                                        ->visible(fn (Get $get, ?Model $record): bool => self::detectDemographicDuplicateForComponent($get, $record) !== null)
                                                        ->heading(fn (Get $get, ?Model $record): ?string => self::detectDemographicDuplicateForComponent($get, $record)['heading'] ?? null)
                                                        ->color(fn (Get $get, ?Model $record): ?string => self::detectDemographicDuplicateForComponent($get, $record)['color'] ?? 'warning')
                                                        ->description(fn (Get $get, ?Model $record): ?HtmlString => self::detectDemographicDuplicateForComponent($get, $record)['description'] ?? null)
                                                        ->actions([
                                                            Action::make('viewDemographicDuplicate')
                                                                ->label(fn (Get $get, ?Model $record): string => self::detectDemographicDuplicateForComponent($get, $record)['action_label'] ?? 'View Record')
                                                                ->url(fn (Get $get, ?Model $record): ?string => self::detectDemographicDuplicateForComponent($get, $record)['action_url'] ?? null)
                                                                ->visible(fn (Get $get, ?Model $record): bool => filled(self::detectDemographicDuplicateForComponent($get, $record)['action_url'] ?? null))
                                                                ->openUrlInNewTab()
                                                                ->color(fn (Get $get, ?Model $record): string => self::detectDemographicDuplicateForComponent($get, $record)['color'] ?? 'warning')
                                                                ->icon('heroicon-m-arrow-top-right-on-square'),
                                                        ]),
                                                ])->columns(3),

                                            Section::make('Emergency Contact')
                                                ->schema([
                                                    TextInput::make("{$profilePrefix}emergency_contact_name")
                                                        ->label('Emergency Contact Name')
                                                        ->live(onBlur: true)
                                                        ->maxLength(255),
                                                    PhoneInput::make("{$profilePrefix}emergency_contact_number")
                                                        ->label('Emergency Contact Phone')
                                                        ->initialCountry('gb'),
                                                    Select::make("{$profilePrefix}emergency_contact_relation_type")
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
                                                        ->native(false)
                                                        ->visible(fn (Get $get): bool => filled($get("{$profilePrefix}emergency_contact_name")))
                                                        ->nullable(),
                                                ])->columns(3),

                                            Section::make('Consent & GDPR')
                                                ->schema([
                                                    Toggle::make('consent_data_storage')
                                                        ->label('Consent for Data Storage')
                                                        ->required(),
                                                    Toggle::make('consent_referrals')
                                                        ->label('Consent for Referrals')
                                                        ->required(),
                                                    Toggle::make('consent_communications')
                                                        ->label('Consent for Communications')
                                                        ->required(),
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
                                                            'gaming' => 'Gaming',
                                                            'drugs' => 'Drugs',
                                                            'gambling' => 'Gambling',
                                                            'pornography' => 'Pornography',
                                                            'smoking' => 'Smoking',
                                                        ])
                                                        ->columns(6),
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
                                                        ->columns(9),
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
                                                        ->columns(6),
                                                    TextInput::make("{$profilePrefix}age_first_used"),
                                                    Toggle::make("{$profilePrefix}overdosed_last_month"),
                                                    Radio::make("{$profilePrefix}injection_history")
                                                        ->options(InjectionHistory::class)->columns(6),
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
                                                        ->options(ReferralType::class)->columns(6),
                                                    TextInput::make("{$profilePrefix}referral_source_specify")
                                                        ->label('Specify source'),
                                                    CheckboxList::make("{$profilePrefix}previous_input")
                                                        ->label('Previous Input')
                                                        ->options([
                                                            'gp' => 'GP',
                                                            'drug_agency' => 'Drug Agency',
                                                            'other' => 'Other',
                                                        ])
                                                        ->columns(6),
                                                    CheckboxList::make("{$profilePrefix}other_issues")
                                                        ->options([
                                                            'criminal_justice' => 'Criminal Justice',
                                                            'housing' => 'Housing',
                                                            'family' => 'Family',
                                                            'finance' => 'Finance',
                                                            'health' => 'Health',
                                                        ])
                                                        ->columns(6),
                                                    Textarea::make("{$profilePrefix}reason_for_referral")
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),

                                    self::TAB_SERVICE_PLAN => Tab::make('Service Plan')
                                        ->icon('heroicon-o-briefcase')
                                        ->schema([
                                            Section::make('Service Assignment')
                                                ->visible(fn (): bool => auth()->user()?->can('Assign:ServiceUser') ?? false)
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
                                                        ->columns(4),
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
                                                        ->columns(6),
                                                    Select::make("{$profilePrefix}treatment_outcome")
                                                        ->options(TreatmentOutcome::class),
                                                    Textarea::make("{$profilePrefix}internal_notes")
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                        ]),
                                ])->columnSpanFull(),

                            ViewField::make('tab_navigation')
                                ->view('filament.components.tab-navigation')
                                ->dehydrated(false)
                                ->columnSpanFull()
                                ->visible($useTabNavigation),
                        ])->collapsible(),

                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array{heading: string, color: string, description: HtmlString, action_label?: string|null, action_url?: string|null}|null
     */
    public static function detectEmailDuplicate(?string $email, ?Model $record): ?array
    {
        if (blank($email)) {
            return null;
        }

        $email = mb_strtolower(mb_trim((string) $email));
        $tempDomain = config('app.temp_email_domain', 'spinney.local');

        if (str_contains($email, '@'.$tempDomain)) {
            return null;
        }

        // 1. Check if an existing Service User has this email
        /** @var ServiceUser|null $serviceUser */
        $serviceUser = ServiceUser::query()
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->where('is_service_user', true)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($serviceUser) {
            $name = e($serviceUser->name);
            $url = rescue(fn () => ServiceUserResource::getUrl('view', [
                'tenant' => Filament::getTenant() ?? $serviceUser->team ?? auth()->user()?->currentTeam,
                'record' => $serviceUser,
            ]));

            return [
                'heading' => 'Service User Already Exists',
                'color' => 'warning',
                'description' => new HtmlString(
                    "A Service User with this email already exists: <strong>{$name}</strong>."
                ),
                'action_label' => 'View Service User Profile',
                'action_url' => $url,
            ];
        }

        // 2. Check if a general Person/Contact exists (not yet a Service User)
        /** @var People|null $person */
        $person = People::query()
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->where('is_service_user', false)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($person) {
            $name = e($person->name);

            return [
                'heading' => 'Person Already on Record as Contact',
                'color' => 'info',
                'description' => new HtmlString(
                    "A contact record for <strong>{$name}</strong> already exists with this email (e.g. from an enquiry or referral). ".
                    'If they are already known, you can promote their existing enquiry or contact record instead of creating a duplicate.'
                ),
                'action_label' => null,
                'action_url' => null,
            ];
        }

        // 3. Check if a Staff User account exists
        /** @var User|null $staffUser */
        $staffUser = User::query()
            ->when($record && $record->user_id, fn (Builder $query) => $query->whereKeyNot($record->user_id))
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($staffUser && ! $staffUser->hasRole('service_user')) {
            $name = e($staffUser->name);

            return [
                'heading' => 'Email Belongs to a Staff Member',
                'color' => 'danger',
                'description' => new HtmlString(
                    "This email is registered to a staff member (<strong>{$name}</strong>). ".
                    'Service user records cannot share an email address with a staff account.'
                ),
                'action_label' => null,
                'action_url' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{heading: string, color: string, description: HtmlString, action_label?: string|null, action_url?: string|null}|null
     */
    public static function detectDemographicDuplicate(?string $firstName, ?string $lastName, mixed $dob, ?string $phone, ?Model $record): ?array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $lastNameClean = filled($lastName) ? mb_strtolower(mb_trim((string) $lastName)) : null;
        $firstNameClean = filled($firstName) ? mb_strtolower(mb_trim((string) $firstName)) : null;

        $dobFormatted = null;
        $dobDisplay = null;
        if (filled($dob)) {
            try {
                $parsed = Carbon::parse((string) $dob);
                $dobFormatted = $parsed->format('Y-m-d');
                $dobDisplay = $parsed->format('d/m/Y');
            } catch (\Throwable) {
                $dobFormatted = null;
            }
        }

        // 1. UK Surgery / NHS standard: Last Name + Date of Birth (and First Name if provided)
        if ($lastNameClean && $dobFormatted) {
            /** @var People|null $demographicMatch */
            $demographicMatch = People::query()
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                ->when($tenantId, fn (Builder $query) => $query->where('team_id', $tenantId))
                ->whereRaw('LOWER(last_name) = ?', [$lastNameClean])
                ->when($firstNameClean, fn (Builder $query) => $query->whereRaw('LOWER(first_name) = ?', [$firstNameClean]))
                ->whereDate('date_of_birth', $dobFormatted)
                ->first();

            if ($demographicMatch) {
                $name = e($demographicMatch->name);
                $isServiceUser = (bool) $demographicMatch->is_service_user;
                $url = $isServiceUser ? rescue(fn () => ServiceUserResource::getUrl('view', [
                    'tenant' => Filament::getTenant() ?? $demographicMatch->team ?? auth()->user()?->currentTeam,
                    'record' => $demographicMatch,
                ])) : null;

                return [
                    'heading' => $isServiceUser
                        ? 'Possible Duplicate: Service User on Record'
                        : 'Possible Duplicate: Contact on Record',
                    'color' => $isServiceUser ? 'warning' : 'info',
                    'description' => new HtmlString(
                        $isServiceUser
                            ? "A Service User with this name and date of birth already exists on record: <strong>{$name}</strong> (DOB: {$dobDisplay})."
                            : "A contact record with this name and date of birth already exists: <strong>{$name}</strong> (DOB: {$dobDisplay})."
                    ),
                    'action_label' => $isServiceUser ? 'View Existing Profile' : null,
                    'action_url' => $url,
                ];
            }
        }

        // 2. Normalized Phone Match
        $normalizedPhone = PhoneNumber::normalize($phone);
        if ($normalizedPhone) {
            /** @var People|null $phoneMatch */
            $phoneMatch = People::query()
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                ->when($tenantId, fn (Builder $query) => $query->where('team_id', $tenantId))
                ->where('phone_normalized', $normalizedPhone)
                ->first();

            if ($phoneMatch) {
                $name = e($phoneMatch->name);
                $isServiceUser = (bool) $phoneMatch->is_service_user;
                $url = $isServiceUser ? rescue(fn () => ServiceUserResource::getUrl('view', [
                    'tenant' => Filament::getTenant() ?? $phoneMatch->team ?? auth()->user()?->currentTeam,
                    'record' => $phoneMatch,
                ])) : null;

                return [
                    'heading' => 'Possible Duplicate: Matching Phone Number',
                    'color' => 'warning',
                    'description' => new HtmlString(
                        "A record with this phone number already exists: <strong>{$name}</strong>."
                    ),
                    'action_label' => $isServiceUser ? 'View Existing Profile' : null,
                    'action_url' => $url,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{heading: string, color: string, description: HtmlString, action_label?: string|null, action_url?: string|null}|null
     */
    public static function detectIdentityDuplicate(Get|\Closure $get, ?Model $record): ?array
    {
        $hasNoEmail = (bool) rescue(fn () => $get('has_no_email'), rescue: false);
        $email = rescue(fn () => $get('email'), rescue: false);

        if (! $hasNoEmail && filled($email)) {
            $emailMatch = self::detectEmailDuplicate($email, $record);
            if ($emailMatch) {
                return $emailMatch;
            }
        }

        return self::detectDemographicDuplicateForComponent($get, $record);
    }

    /**
     * @return array{heading: string, color: string, description: HtmlString, action_label?: string|null, action_url?: string|null}|null
     */
    public static function detectDemographicDuplicateForComponent(Get|\Closure $get, ?Model $record): ?array
    {
        return self::detectDemographicDuplicate(
            rescue(fn () => $get('first_name'), rescue: false),
            rescue(fn () => $get('last_name'), rescue: false),
            rescue(fn () => $get('date_of_birth'), rescue: false),
            rescue(fn () => $get('phone'), rescue: false),
            $record,
        );
    }
}
