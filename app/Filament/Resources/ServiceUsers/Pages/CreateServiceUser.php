<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Enums\CreationSource;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateServiceUser extends CreateRecord
{
    protected static string $resource = ServiceUserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Extract password if provided
        $password = $data['password'] ?? Str::random(12);
        unset($data['password']);

        // 2. Extract profile data
        $profileFields = [
            'addictions', 'substances_used', 'frequency_of_use', 'amount_of_use',
            'route_of_use', 'age_first_used', 'overdosed_last_month', 'injection_history',
            'registered_with_gp', 'gp_name', 'gp_address',
            'referral_type', 'referral_source_specify', 'previous_input', 'other_issues',
            'reason_for_referral', 'target_service_team', 'engagement_status',
            'referral_targets', 'referral_agency_specify', 'intervention_offered',
            'treatment_outcome', 'internal_notes',
        ];

        $profileData = Arr::only($data['profile'] ?? [], $profileFields);
        $identityData = Arr::except($data, ['password', 'profile']);

        $identityData['creation_source'] = CreationSource::WEB->value;

        // 3. Create the ServiceUser (People) record
        /** @var ServiceUser $record */
        $record = parent::handleRecordCreation($identityData);

        // 4. Create the ServiceUserProfile
        if (! empty($profileData)) {
            $record->profile()->create([
                'team_id' => $record->team_id,
                ...$profileData,
            ]);
        }

        // 5. Create the linked User account
        $user = User::create([
            'name' => $record->name,
            'email' => $record->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('service_user');

        // 6. Link User to ServiceUser
        $record->update(['user_id' => $user->id]);

        return $record;
    }
}
