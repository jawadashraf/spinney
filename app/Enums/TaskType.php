<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskType: string implements HasColor, HasIcon, HasLabel
{
    case GeneralTask = 'general_task';
    case FollowUpCall = 'follow_up_call';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GeneralTask => 'General Task',
            self::FollowUpCall => 'Follow-up Call',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GeneralTask => 'primary',
            self::FollowUpCall => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::GeneralTask => 'heroicon-o-check-circle',
            self::FollowUpCall => 'heroicon-o-phone',
        };
    }
}
