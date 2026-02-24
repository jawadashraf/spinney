<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\EngagementStatus;
use App\Enums\EnquiryStatus;
use App\Enums\ServiceTeam;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;
use App\Notifications\ServiceUserPromotedNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
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
            ->icon(Heroicon::UserPlus)
            ->color(Color::Blue)
            ->authorize('convertToServiceUser')
            ->hidden(fn (Enquiry $record): bool => ! $record->canBeConverted())
            ->modalHeading('Convert Enquiry to Service User')
            ->modalDescription('This will promote the caller to a formal Service User record and capture essential case file details.')
            ->modalWidth('4xl')
            ->form([
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

                Section::make('Initial Assessment')
                    ->schema([
                        Textarea::make('presenting_issues')
                            ->label('Presenting Issues')
                            ->rows(3)
                            ->default(fn (Enquiry $record) => $record->reason_for_contact),
                        Textarea::make('risk_summary')
                            ->label('Risk Summary')
                            ->rows(3)
                            ->default(fn (Enquiry $record) => $record->risk_flags),
                        Textarea::make('faith_cultural_sensitivity')
                            ->label('Faith & Cultural Sensitivity')
                            ->rows(2),
                    ]),

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
            ])
            ->action(function (array $data, Enquiry $record): void {
                DB::transaction(function () use ($data, $record) {
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
                    $person->update([
                        'email' => $data['email'],
                        'user_id' => $user->id,
                        'is_service_user' => true,
                        'type' => 'service_user',
                    ]);

                    // 4. Update Enquiry Status
                    $record->update([
                        'status' => EnquiryStatus::CONVERTED,
                        'converted_at' => now(),
                    ]);

                    // 5. Notify Staff (super_admin and safeguarding)
                    User::role(['super_admin', 'safeguarding'])->get()->each(function (User $staff) use ($person) {
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
