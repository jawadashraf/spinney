<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TreatmentOutcome: string implements HasLabel
{
    case DRUG_FREE = 'drug_free';
    case ALCOHOL_FREE = 'alcohol_free';
    case OCCASIONAL = 'occasional';
    case DROP_OUT = 'drop_out';
    case PEER_SUPPORT = 'peer_support';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRUG_FREE => 'TREATMENT COMPLETED (Drug Free)',
            self::ALCOHOL_FREE => 'TREATMENT COMPLETED (Alcohol Free)',
            self::OCCASIONAL => 'TREATMENT COMPLETE (Occasional User)',
            self::DROP_OUT => 'INCOMPLETED (Drop Out)',
            self::PEER_SUPPORT => 'PEER SUPPORT',
        };
    }
}
