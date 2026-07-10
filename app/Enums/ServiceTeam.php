<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceTeam: string implements HasLabel
{
    case ASSESSMENT = 'assessment';
    case DRUG_ALCOHOL = 'drug_alcohol';
    case SPIRITUAL = 'spiritual';
    case EDUCATION_OUTREACH = 'education_outreach';
    case AFTERCARE = 'aftercare';

    public function getLabel(): string
    {
        return match ($this) {
            self::ASSESSMENT => 'Assessment',
            self::DRUG_ALCOHOL => 'Drug & Alcohol',
            self::SPIRITUAL => 'Spiritual Support',
            self::EDUCATION_OUTREACH => 'Education & Outreach',
            self::AFTERCARE => 'Aftercare',
        };
    }
}
