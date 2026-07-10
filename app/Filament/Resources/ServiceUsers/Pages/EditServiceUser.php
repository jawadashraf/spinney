<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Pages;

use App\Filament\Resources\ServiceUsers\Concerns\HasServiceUserTabNavigation;
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
    use HasServiceUserTabNavigation;

    protected static string $resource = ServiceUserResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): string
    {
        return 'Details & Profile';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ServiceUser $record */
        $record = $this->record;
        $profile = $record->profile;

        if ($profile) {
            $data['profile'] = $profile->toArray();
        }

        $emergencyContact = $record->emergencyContacts()->first();
        $data['emergency_contact_id'] = $emergencyContact?->id;
        $data['emergency_contact_relation_type'] = $emergencyContact?->pivot?->relation_type;

        return $data;
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

        $emergencyContactId = $data['emergency_contact_id'] ?? null;
        $emergencyContactRelationType = $data['emergency_contact_relation_type'] ?? null;
        $profileData = Arr::only($data['profile'] ?? [], $profileFields);
        $identityData = Arr::except($data, ['password', 'profile', 'emergency_contact_id', 'emergency_contact_relation_type']);

        $record = parent::handleRecordUpdate($record, $identityData);

        /** @var ServiceUser $record */
        if (! empty($profileData)) {
            $record->profile()->updateOrCreate(
                ['team_id' => $record->team_id],
                $profileData
            );
        }

        $record->syncEmergencyContact(
            $emergencyContactId ? (int) $emergencyContactId : null,
            $emergencyContactRelationType ? (string) $emergencyContactRelationType : null,
        );

        if ($record->user && $record->email !== $oldEmail) {
            $record->user->update([
                'email' => $record->email,
            ]);
        }

        return $record;
    }
}
