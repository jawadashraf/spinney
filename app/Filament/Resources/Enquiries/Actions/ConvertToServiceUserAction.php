<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\EngagementStatus;
use App\Enums\EnquiryStatus;
use App\Enums\InjectionHistory;
use App\Enums\ReferralType;
use App\Enums\ServiceTeam;
use App\Enums\SubstanceUseFrequency;
use App\Enums\TreatmentOutcome;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;
use App\Notifications\ServiceUserPromotedNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Colors\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class ConvertToServiceUserAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'convertToServiceUser';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Promote to Service User')
            ->icon('heroicon-o-user-plus')
            ->color(Color::Blue)
            ->authorize('convertToServiceUser')
            ->hidden(fn (Enquiry $record): bool => ! $record->canBeConverted())
            ->modalHeading('Convert Enquiry to Service User')
            ->modalDescription('This will promote the caller to a formal Service User record and capture essential case file details.')
            ->modalWidth('4xl')
            ->schema([
                Tabs::make('Profile Information')
                    ->tabs([
                        Tab::make('Profile & Consent')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('User Account')
                                    ->schema([
                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->default(fn (Enquiry $record) => $record->people?->email),
                                        TextInput::make('password')
                                            ->password()
                                            ->helperText('Leave blank to auto-generate a secure password.')
                                            ->dehydrated(true),
                                    ])->columns(2),

                                Section::make('Demographics')
                                    ->schema([
                                        DatePicker::make('date_of_birth'),
                                        Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'other' => 'Other',
                                            ]),
                                        TextInput::make('ethnicity'),
                                        TextInput::make('phone')
                                            ->tel(),
                                        TextInput::make('postcode'),
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
                                        TextInput::make('emergency_contact_number')
                                            ->tel(),
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
                                        CheckboxList::make('addictions')
                                            ->options([
                                                'smoking' => 'Smoking',
                                                'drugs' => 'Drugs',
                                                'gambling' => 'Gambling',
                                                'compulsive_behavior' => 'Compulsive Behavior',
                                                'pornography' => 'Pornography',
                                            ])
                                            ->columns(3),
                                        CheckboxList::make('substances_used')
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
                                        Select::make('frequency_of_use')
                                            ->options(SubstanceUseFrequency::class),
                                        TextInput::make('amount_of_use'),
                                        CheckboxList::make('route_of_use')
                                            ->options([
                                                'smoke' => 'Smoke',
                                                'sniff' => 'Sniff',
                                                'oral' => 'Oral',
                                                'inject' => 'Inject',
                                            ])
                                            ->columns(4),
                                        TextInput::make('age_first_used'),
                                        Toggle::make('overdosed_last_month'),
                                        Radio::make('injection_history')
                                            ->options(InjectionHistory::class),
                                    ]),

                                Section::make('GP & Health')
                                    ->schema([
                                        Toggle::make('registered_with_gp')
                                            ->live(),
                                        TextInput::make('gp_name')
                                            ->visible(fn ($get) => $get('registered_with_gp') ?? false),
                                        Textarea::make('gp_address')
                                            ->rows(2)
                                            ->visible(fn ($get) => $get('registered_with_gp') ?? false)
                                            ->columnSpanFull(),
                                    ])->columns(2),
                            ]),

                        Tab::make('Referral')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make('Referral Details')
                                    ->schema([
                                        Radio::make('referral_type')
                                            ->options(ReferralType::class),
                                        TextInput::make('referral_source_specify')
                                            ->label('Specify source'),
                                        CheckboxList::make('previous_input')
                                            ->label('Previous Input')
                                            ->options([
                                                'gp' => 'GP',
                                                'drug_agency' => 'Drug Agency',
                                                'other' => 'Other',
                                            ])
                                            ->columns(3),
                                        CheckboxList::make('other_issues')
                                            ->options([
                                                'criminal_justice' => 'Criminal Justice',
                                                'housing' => 'Housing',
                                                'family' => 'Family',
                                                'finance' => 'Finance',
                                                'health' => 'Health',
                                            ])
                                            ->columns(3),
                                        Textarea::make('reason_for_referral')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Service Plan')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Service Assignment')
                                    ->schema([
                                        Select::make('target_service_team')
                                            ->label('Service Team')
                                            ->options(ServiceTeam::class)
                                            ->native(false)
                                            ->required(),
                                        Select::make('engagement_status')
                                            ->options(EngagementStatus::class)
                                            ->native(false)
                                            ->default(EngagementStatus::ACTIVE->value)
                                            ->required(),
                                    ])->columns(2),

                                Section::make('Plan & Outcomes')
                                    ->schema([
                                        CheckboxList::make('referral_targets')
                                            ->label('Next Steps (Referrals)')
                                            ->options([
                                                'spiritual' => 'Referral to Spiritual Team',
                                                'turning_point' => 'Referral to Turning Point',
                                                'alternative_therapy' => 'Referral to Alternative Therapy',
                                                'family_support' => 'Referral to Family support',
                                            ])
                                            ->columns(2),
                                        TextInput::make('referral_agency_specify')
                                            ->label('Specify Agency'),
                                        CheckboxList::make('intervention_offered')
                                            ->options([
                                                'quran' => 'Qur\'an class',
                                                'group_therapy' => 'Group therapy',
                                                'gym' => 'Gym',
                                                'spiritual' => 'Spiritual',
                                                'family_support' => 'Family support',
                                            ])
                                            ->columns(3),
                                        Select::make('treatment_outcome')
                                            ->options(TreatmentOutcome::class),
                                        Textarea::make('internal_notes')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->action(function (array $data, Enquiry $record): void {
                DB::transaction(function () use ($data, $record): void {
                    /** @var People $person */
                    $person = $record->people;

                    // 1. Create User
                    $password = $data['password'] ?? Str::random(12);
                    $user = User::create([
                        'name' => $person->name,
                        'email' => $data['email'],
                        'password' => Hash::make($password),
                    ]);

                    // 2. Assign Service User Role
                    $user->assignRole('service_user');

                    // 3. Link Person to User and update details
                    $profileFields = [
                        'addictions', 'substances_used', 'frequency_of_use', 'amount_of_use',
                        'route_of_use', 'age_first_used', 'overdosed_last_month', 'injection_history',
                        'registered_with_gp', 'gp_name', 'gp_address',
                        'referral_type', 'referral_source_specify', 'previous_input', 'other_issues',
                        'reason_for_referral', 'target_service_team', 'engagement_status',
                        'referral_targets', 'referral_agency_specify', 'intervention_offered',
                        'treatment_outcome', 'internal_notes',
                    ];

                    $profileData = Arr::only($data, $profileFields);
                    $identityData = Arr::except($data, array_merge($profileFields, ['password']));

                    $person->update(array_merge($identityData, [
                        'user_id' => $user->id,
                        'is_service_user' => true,
                        'type' => 'service_user',
                    ]));

                    $person->serviceUserProfile()->updateOrCreate(
                        ['team_id' => $person->team_id],
                        $profileData
                    );

                    // 4. Update Enquiry Status
                    $record->update([
                        'status' => EnquiryStatus::CONVERTED,
                        'converted_at' => now(),
                    ]);

                    // 5. Notify Staff (super_admin and safeguarding)
                    User::role(['super_admin', 'safeguarding'])->get()->each(function (User $staff) use ($person): void {
                        $staff->notify(new ServiceUserPromotedNotification($person));
                    });
                });

                Notification::make()
                    ->title('Success')
                    ->body('Enquiry has been converted, user account created, and staff notified.')
                    ->success()
                    ->send();
            });
    }
}
