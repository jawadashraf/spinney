<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EnquiryCallType: string implements HasColor, HasIcon, HasLabel
{
    case GENERAL = 'general';
    case FOLLOW_UP = 'follow_up';
    case CHECK_IN = 'check_in';
    case SCHEDULED = 'scheduled';
    case EMERGENCY = 'emergency';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GENERAL => 'General Enquiry',
            self::FOLLOW_UP => 'Follow-up Call',
            self::CHECK_IN => 'Check-in Call',
            self::SCHEDULED => 'Scheduled Call',
            self::EMERGENCY => 'Emergency Response',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GENERAL => 'primary',
            self::FOLLOW_UP => 'warning',
            self::CHECK_IN => 'info',
            self::SCHEDULED => 'success',
            self::EMERGENCY => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::GENERAL => 'heroicon-o-question-mark-circle',
            self::FOLLOW_UP => 'heroicon-o-arrow-path',
            self::CHECK_IN => 'heroicon-o-chat-bubble-left-right',
            self::SCHEDULED => 'heroicon-o-calendar',
            self::EMERGENCY => 'heroicon-o-exclamation-triangle',
        };
    }
}
