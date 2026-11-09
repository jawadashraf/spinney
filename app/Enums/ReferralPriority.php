<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReferralPriority: string implements HasColor, HasIcon, HasLabel
{
    case Emergency = 'EMERGENCY';
    case Urgent = 'Urgent';
    case RoutineUrgent = 'Routine / urgent';
    case Routine = 'Routine';
    case CheckAvailability = 'Check availability';

    public function getLabel(): string
    {
        return match ($this) {
            self::Emergency => 'Emergency (999)',
            self::Urgent => 'Urgent',
            self::RoutineUrgent => 'Routine / Urgent',
            self::Routine => 'Routine',
            self::CheckAvailability => 'Check Availability',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Emergency => 'danger',
            self::Urgent => 'warning',
            self::RoutineUrgent => 'info',
            self::Routine => 'gray',
            self::CheckAvailability => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Emergency => 'heroicon-m-exclamation-triangle',
            self::Urgent => 'heroicon-m-clock',
            default => null,
        };
    }

    public static function tryNormalize(?string $value): self
    {
        if ($value === null) {
            return self::Routine;
        }

        $clean = trim($value);

        return match ($clean) {
            'EMERGENCY', 'Emergency' => self::Emergency,
            'Urgent', 'Urgent — confirm' => self::Urgent,
            'Routine / urgent', 'Urgent / routine' => self::RoutineUrgent,
            'Check availability' => self::CheckAvailability,
            default => self::tryFrom($clean) ?? self::Routine,
        };
    }
}
