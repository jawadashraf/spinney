<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CounselorType: string implements HasLabel
{
    case SPIRITUAL = 'spiritual';
    case DRUG = 'drug';
    case EDUCATION = 'education';
    case OUTREACH = 'outreach';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPIRITUAL => 'Spiritual Counselor',
            self::DRUG => 'Drug Counselor',
            self::EDUCATION => 'Education Counselor',
            self::OUTREACH => 'Outreach Worker',
        };
    }
}
