<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CallOutcome: string implements HasColor, HasIcon, HasLabel
{
    case Answered = 'answered';
    case NoAnswer = 'no_answer';
    case Voicemail = 'voicemail';
    case WrongNumber = 'wrong_number';
    case Declined = 'declined';

    public function getLabel(): string
    {
        return match ($this) {
            self::Answered => 'Answered',
            self::NoAnswer => 'No answer',
            self::Voicemail => 'Voicemail left',
            self::WrongNumber => 'Wrong number',
            self::Declined => 'Declined to talk',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Answered => 'success',
            self::NoAnswer => 'gray',
            self::Voicemail => 'warning',
            self::WrongNumber, self::Declined => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Answered => 'heroicon-o-phone',
            self::NoAnswer => 'heroicon-o-phone-x-mark',
            self::Voicemail => 'heroicon-o-chat-bubble-bottom-center-text',
            self::WrongNumber => 'heroicon-o-exclamation-triangle',
            self::Declined => 'heroicon-o-hand-raised',
        };
    }

    public function isSuccessfulContact(): bool
    {
        return $this === self::Answered;
    }
}
