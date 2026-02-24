<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

use App\Enums\CustomFieldType;

/**
 * People custom field codes
 */
enum PeopleField: string
{
    use CustomFieldTrait;

    case EMAILS = 'emails';
    case PHONE_NUMBER = 'phone_number';
    case JOB_TITLE = 'job_title';
    case LINKEDIN = 'linkedin';
    case CONSENT_DATA_STORAGE = 'consent_data_storage';
    case CONSENT_REFERRALS = 'consent_referrals';
    case CONSENT_COMMUNICATIONS = 'consent_communications';
    case PRESENTING_ISSUES = 'presenting_issues';
    case RISK_SUMMARY = 'risk_summary';
    case FAITH_CULTURAL_SENSITIVITY = 'faith_cultural_sensitivity';
    case SERVICE_TEAM = 'service_team';
    case ENGAGEMENT_STATUS = 'engagement_status';

    public function getFieldType(): string
    {
        return match ($this) {
            self::EMAILS => CustomFieldType::TAGS_INPUT->value,
            self::PHONE_NUMBER, self::JOB_TITLE => CustomFieldType::TEXT->value,
            self::LINKEDIN => CustomFieldType::LINK->value,
            self::CONSENT_DATA_STORAGE, self::CONSENT_REFERRALS, self::CONSENT_COMMUNICATIONS => CustomFieldType::TOGGLE->value,
            self::PRESENTING_ISSUES, self::RISK_SUMMARY, self::FAITH_CULTURAL_SENSITIVITY => CustomFieldType::TEXTAREA->value,
            self::SERVICE_TEAM, self::ENGAGEMENT_STATUS => CustomFieldType::SELECT->value,
        };
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::EMAILS => 'Emails',
            self::PHONE_NUMBER => 'Phone Number',
            self::JOB_TITLE => 'Job Title',
            self::LINKEDIN => 'LinkedIn',
            self::CONSENT_DATA_STORAGE => 'Consent: Data Storage',
            self::CONSENT_REFERRALS => 'Consent: Referrals',
            self::CONSENT_COMMUNICATIONS => 'Consent: Communications',
            self::PRESENTING_ISSUES => 'Presenting Issues',
            self::RISK_SUMMARY => 'Risk Summary',
            self::FAITH_CULTURAL_SENSITIVITY => 'Faith & Cultural Sensitivity',
            self::SERVICE_TEAM => 'Service Team',
            self::ENGAGEMENT_STATUS => 'Engagement Status',
        };
    }

    public function isListToggleableHidden(): bool
    {
        return match ($this) {
            self::JOB_TITLE, self::EMAILS => false,
            default => true,
        };
    }

    public function getOptions(): ?array
    {
        return match ($this) {
            self::SERVICE_TEAM => array_reduce(\App\Enums\ServiceTeam::cases(), fn ($acc, $case): array => $acc + [$case->value => $case->getLabel()], []),
            self::ENGAGEMENT_STATUS => array_reduce(\App\Enums\EngagementStatus::cases(), fn ($acc, $case): array => $acc + [$case->value => $case->getLabel()], []),
            default => null,
        };
    }

    public function getOptionColors(): ?array
    {
        return match ($this) {
            self::ENGAGEMENT_STATUS => array_reduce(\App\Enums\EngagementStatus::cases(), fn ($acc, $case): array => $acc + [$case->value => $case->getColor()], []),
            default => null,
        };
    }
}
