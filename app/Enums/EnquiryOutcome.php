<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnquiryOutcome: string implements HasColor, HasLabel
{
    case ANSWERED = 'answered';
    case NO_ANSWER = 'no_answer';
    case VOICEMAIL = 'voicemail';
    case CALLBACK_REQUIRED = 'callback_required';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ANSWERED => 'Answered',
            self::NO_ANSWER => 'No Answer',
            self::VOICEMAIL => 'Voicemail Left',
            self::CALLBACK_REQUIRED => 'Callback Required',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ANSWERED => 'success',
            self::NO_ANSWER => 'gray',
            self::VOICEMAIL => 'warning',
            self::CALLBACK_REQUIRED => 'danger',
        };
    }
}
