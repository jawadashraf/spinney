<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SafeguardingAlertsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            Stat::make('Safeguarding Flags', Enquiry::where('safeguarding_flags', true)
                ->whereNotIn('status', [EnquiryStatus::CLOSED, EnquiryStatus::CONVERTED])
                ->count())
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Active safeguarding concerns'),

            Stat::make('Emergency Calls', Enquiry::where('call_type', EnquiryCallType::EMERGENCY)
                ->whereNotIn('status', [EnquiryStatus::CLOSED, EnquiryStatus::CONVERTED])
                ->count())
                ->color('danger')
                ->icon('heroicon-o-bell-alert')
                ->description('Unresolved emergency responses'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
