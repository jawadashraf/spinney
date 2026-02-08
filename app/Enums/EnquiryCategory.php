<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EnquiryCategory: string implements HasColor, HasIcon, HasLabel
{
    case FAMILY_ADVICE = 'family_advice';
    case SELF_HELP = 'self_help';
    case EDUCATION_OUTREACH = 'education_outreach';
    case DONATION = 'donation';
    case FOOD_BANK = 'food_bank';
    case DOMESTIC_ISSUES = 'domestic_issues';
    case MENTAL_HEALTH = 'mental_health';
    case COMMUNITY_SUPPORT = 'community_support';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FAMILY_ADVICE => 'Family Advice',
            self::SELF_HELP => 'Help for Self',
            self::EDUCATION_OUTREACH => 'School/Madrassa',
            self::DONATION => 'Donation Offer',
            self::FOOD_BANK => 'Food Bank Referral',
            self::DOMESTIC_ISSUES => 'Domestic Issues',
            self::MENTAL_HEALTH => 'Mental Health Support',
            self::COMMUNITY_SUPPORT => 'General Community Support',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FAMILY_ADVICE => 'info',
            self::SELF_HELP => 'primary',
            self::EDUCATION_OUTREACH => 'warning',
            self::DONATION => 'success',
            self::FOOD_BANK => 'info',
            self::DOMESTIC_ISSUES => 'danger',
            self::MENTAL_HEALTH => 'danger',
            self::COMMUNITY_SUPPORT => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FAMILY_ADVICE => 'heroicon-m-users',
            self::SELF_HELP => 'heroicon-m-user',
            self::EDUCATION_OUTREACH => 'heroicon-m-academic-cap',
            self::DONATION => 'heroicon-m-heart',
            self::FOOD_BANK => 'heroicon-m-shopping-bag',
            self::DOMESTIC_ISSUES => 'heroicon-m-home',
            self::MENTAL_HEALTH => 'heroicon-m-face-smile',
            self::COMMUNITY_SUPPORT => 'heroicon-m-globe-alt',
        };
    }
}
