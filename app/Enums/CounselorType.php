<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The service an appointment (or a counsellor's availability) is for, per requirements §6 and AC AP-002.
 */
enum CounselorType: string implements HasLabel
{
    case ASSESSMENT = 'assessment';
    case DRUG = 'drug';
    case SPIRITUAL = 'spiritual';
    case EDUCATION = 'education';
    case OUTREACH = 'outreach';
    case AFTERCARE = 'aftercare';

    public function getLabel(): string
    {
        return match ($this) {
            self::ASSESSMENT => 'Assessment',
            self::DRUG => 'Drug & Alcohol',
            self::SPIRITUAL => 'Spiritual Counselling',
            self::EDUCATION => 'Education & Support',
            self::OUTREACH => 'Outreach',
            self::AFTERCARE => 'Aftercare',
        };
    }
}
