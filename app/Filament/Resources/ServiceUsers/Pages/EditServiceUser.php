<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class EditServiceUser extends EditRecord
{
    protected static string $resource = ServiceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ServiceUser $record */
        $oldEmail = $record->email;

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

        $record = parent::handleRecordUpdate($record, $identityData);

        if (! empty($profileData)) {
            $record->profile()->updateOrCreate(
                ['team_id' => $record->team_id],
                $profileData
            );
        }

        if ($record->user && $record->email !== $oldEmail) {
            $record->user->update([
                'email' => $record->email,
            ]);
        }

        return $record;
    }
}
