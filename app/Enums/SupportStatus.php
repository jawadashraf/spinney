<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SupportStatus: string implements HasColor, HasIcon, HasLabel
{
    case Normal = 'normal';
    case NeedsAttention = 'needs_attention';
    case UrgentAttention = 'urgent_attention';
    case Resolved = 'resolved';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::NeedsAttention => 'Needs Attention',
            self::UrgentAttention => 'Urgent Attention',
            self::Resolved => 'Resolved',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => 'gray',
            self::NeedsAttention => 'warning',
            self::UrgentAttention => 'danger',
            self::Resolved => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Normal => 'heroicon-o-check-circle',
            self::NeedsAttention => 'heroicon-o-exclamation-circle',
            self::UrgentAttention => 'heroicon-o-exclamation-triangle',
            self::Resolved => 'heroicon-o-check-badge',
        };
    }
}
