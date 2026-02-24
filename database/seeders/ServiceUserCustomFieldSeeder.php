<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CustomFields\CustomFieldSectionType;
use App\Enums\CustomFields\PeopleField;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class ServiceUserCustomFieldSeeder extends Seeder
{
    public function run(): void
    {
        // Sync fields globally (no team)
        $this->syncFieldsForTeam(null);
    }

    private function syncFieldsForTeam(?Team $team): void
    {
        $teamId = $team?->id;

        // 1. Create a "Service User Case File" section
        $section = CustomFieldSection::updateOrCreate([
            'team_id' => $teamId,
            'entity_type' => People::class,
            'code' => 'service_user_case_file',
        ], [
            'name' => 'Service User Case File',
            'type' => CustomFieldSectionType::SECTION,
            'active' => true,
        ]);

        $fields = [
            PeopleField::CONSENT_DATA_STORAGE,
            PeopleField::CONSENT_REFERRALS,
            PeopleField::CONSENT_COMMUNICATIONS,
            PeopleField::PRESENTING_ISSUES,
            PeopleField::RISK_SUMMARY,
            PeopleField::FAITH_CULTURAL_SENSITIVITY,
            PeopleField::SERVICE_TEAM,
            PeopleField::ENGAGEMENT_STATUS,
        ];

        foreach ($fields as $index => $fieldEnum) {
            $config = $fieldEnum->getConfiguration();

            $field = CustomField::updateOrCreate([
                'team_id' => $teamId,
                'entity_type' => People::class,
                'code' => $fieldEnum->value,
            ], [
                'custom_field_section_id' => $section->id,
                'name' => $config['name'],
                'type' => $config['type'],
                'system_defined' => true,
                'active' => true,
                'sort_order' => $index,
            ]);

            // Sync options if applicable
            if ($config['options']) {
                $field->options()->delete();
                $sortOrder = 0;
                foreach ($config['options'] as $value => $label) {
                    $field->options()->create([
                        'team_id' => $teamId,
                        'name' => $label,
                        // We might need to store the value somewhere,
                        // but usually name is used as the underlying value in select options
                        'settings' => ['value' => $value],
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }
    }
}
