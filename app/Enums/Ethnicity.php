<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Ethnicity: string implements HasLabel
{
    case WhiteBritish = 'white_british';
    case WhiteIrish = 'white_irish';
    case WhiteOther = 'white_other';
    case MixedWhiteBlackCaribbean = 'mixed_white_black_caribbean';
    case MixedWhiteBlackAfrican = 'mixed_white_black_african';
    case MixedWhiteAsian = 'mixed_white_asian';
    case MixedOther = 'mixed_other';
    case AsianIndian = 'asian_indian';
    case AsianPakistani = 'asian_pakistani';
    case AsianBangladeshi = 'asian_bangladeshi';
    case AsianChinese = 'asian_chinese';
    case AsianOther = 'asian_other';
    case BlackAfrican = 'black_african';
    case BlackCaribbean = 'black_caribbean';
    case BlackOther = 'black_other';
    case Arab = 'arab';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WhiteBritish => 'White British',
            self::WhiteIrish => 'White Irish',
            self::WhiteOther => 'White Other',
            self::MixedWhiteBlackCaribbean => 'Mixed: White & Black Caribbean',
            self::MixedWhiteBlackAfrican => 'Mixed: White & Black African',
            self::MixedWhiteAsian => 'Mixed: White & Asian',
            self::MixedOther => 'Mixed Other',
            self::AsianIndian => 'Asian / Asian British: Indian',
            self::AsianPakistani => 'Asian / Asian British: Pakistani',
            self::AsianBangladeshi => 'Asian / Asian British: Bangladeshi',
            self::AsianChinese => 'Asian / Asian British: Chinese',
            self::AsianOther => 'Asian / Asian British: Other',
            self::BlackAfrican => 'Black / Black British: African',
            self::BlackCaribbean => 'Black / Black British: Caribbean',
            self::BlackOther => 'Black / Black British: Other',
            self::Arab => 'Arab',
            self::Other => 'Other',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }
}
