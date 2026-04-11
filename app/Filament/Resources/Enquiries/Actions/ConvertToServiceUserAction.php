<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Enums\EnquiryStatus;
use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;
use App\Notifications\ServiceUserPromotedNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
            ->schema(fn (Enquiry $record): array => ServiceUserForm::getComponents(''))
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
                    User::role(['admin'])->get()->each(function (User $staff) use ($person): void {
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
