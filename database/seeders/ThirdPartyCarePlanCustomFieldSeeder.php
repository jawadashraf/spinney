<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CustomFields\CustomFieldSectionType;
use App\Enums\CustomFields\ThirdPartyCarePlanField;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use App\Models\Team;
use App\Models\ThirdPartyCarePlan;
use Illuminate\Database\Seeder;

final class ThirdPartyCarePlanCustomFieldSeeder extends Seeder
{
    public function run(): void
    {
        // Sync fields globally (no team)
        $this->syncFieldsForTeam(null);
    }

    private function syncFieldsForTeam(?Team $team): void
    {
        $teamId = $team?->id;

        // 1. Create a "Treatment Details" section
        $section = CustomFieldSection::updateOrCreate([
            'team_id' => $teamId,
            'entity_type' => ThirdPartyCarePlan::class,
            'code' => 'treatment_details',
        ], [
            'name' => 'Treatment Details',
            'type' => CustomFieldSectionType::SECTION,
            'active' => true,
        ]);

        $fields = [
            ThirdPartyCarePlanField::TREATMENT_GOALS,
            ThirdPartyCarePlanField::PRESENTING_ISSUES_DETAILED,
            ThirdPartyCarePlanField::RISK_ASSESSMENT,
            ThirdPartyCarePlanField::MEDICATION_DETAILS,
            ThirdPartyCarePlanField::THERAPEUTIC_INTERVENTIONS,
            ThirdPartyCarePlanField::PROGRESS_NOTES,
            ThirdPartyCarePlanField::TREATMENT_OUTCOMES,
            ThirdPartyCarePlanField::DISCHARGE_SUMMARY,
            ThirdPartyCarePlanField::FOLLOW_UP_PLAN,
            ThirdPartyCarePlanField::ADDITIONAL_NOTES,
        ];

        foreach ($fields as $index => $fieldEnum) {
            $config = $fieldEnum->getConfiguration();

            CustomField::updateOrCreate([
                'team_id' => $teamId,
                'entity_type' => ThirdPartyCarePlan::class,
                'code' => $fieldEnum->value,
            ], [
                'custom_field_section_id' => $section->id,
                'name' => $config['name'],
                'type' => $config['type'],
                'system_defined' => true,
                'active' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
