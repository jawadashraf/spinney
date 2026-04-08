<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

/**
 * Third-party care plan custom field codes
 */
enum ThirdPartyCarePlanField: string
{
    use CustomFieldTrait;

    case TREATMENT_GOALS = 'treatment_goals';
    case PRESENTING_ISSUES_DETAILED = 'presenting_issues_detailed';
    case RISK_ASSESSMENT = 'risk_assessment';
    case MEDICATION_DETAILS = 'medication_details';
    case THERAPEUTIC_INTERVENTIONS = 'therapeutic_interventions';
    case PROGRESS_NOTES = 'progress_notes';
    case TREATMENT_OUTCOMES = 'treatment_outcomes';
    case DISCHARGE_SUMMARY = 'discharge_summary';
    case FOLLOW_UP_PLAN = 'follow_up_plan';
    case ADDITIONAL_NOTES = 'additional_notes';

    public function getFieldType(): string
    {
        return match ($this) {
            self::TREATMENT_GOALS,
            self::PRESENTING_ISSUES_DETAILED,
            self::RISK_ASSESSMENT,
            self::MEDICATION_DETAILS,
            self::THERAPEUTIC_INTERVENTIONS,
            self::PROGRESS_NOTES,
            self::TREATMENT_OUTCOMES,
            self::DISCHARGE_SUMMARY,
            self::FOLLOW_UP_PLAN,
            self::ADDITIONAL_NOTES => CustomFieldType::TEXTAREA->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::TREATMENT_GOALS => 'Treatment Goals',
            self::PRESENTING_ISSUES_DETAILED => 'Presenting Issues (Detailed)',
            self::RISK_ASSESSMENT => 'Risk Assessment',
            self::MEDICATION_DETAILS => 'Medication Details',
            self::THERAPEUTIC_INTERVENTIONS => 'Therapeutic Interventions',
            self::PROGRESS_NOTES => 'Progress Notes',
            self::TREATMENT_OUTCOMES => 'Treatment Outcomes',
            self::DISCHARGE_SUMMARY => 'Discharge Summary',
            self::FOLLOW_UP_PLAN => 'Follow-Up Plan',
            self::ADDITIONAL_NOTES => 'Additional Notes',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::TREATMENT_GOALS => 'Primary goals and objectives of the treatment plan',
            self::PRESENTING_ISSUES_DETAILED => 'Comprehensive description of issues presented by the service user',
            self::RISK_ASSESSMENT => 'Assessment of risks and safeguarding concerns',
            self::MEDICATION_DETAILS => 'Current medications and prescribing information',
            self::THERAPEUTIC_INTERVENTIONS => 'Planned therapeutic interventions and approaches',
            self::PROGRESS_NOTES => 'Ongoing progress updates from third-party provider',
            self::TREATMENT_OUTCOMES => 'Measured outcomes and achievements',
            self::DISCHARGE_SUMMARY => 'Summary of care upon completion',
            self::FOLLOW_UP_PLAN => 'Post-discharge follow-up schedule and actions',
            self::ADDITIONAL_NOTES => 'Any additional notes or observations',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return true;
    }
}
