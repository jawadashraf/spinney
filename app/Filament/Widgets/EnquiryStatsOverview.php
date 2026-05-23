<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class EnquiryStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Open Enquiries', Enquiry::where('status', EnquiryStatus::OPEN)->count())
                ->color('info')
                ->icon('heroicon-o-inbox-arrow-down'),

            Stat::make('In Progress', Enquiry::where('status', EnquiryStatus::IN_PROGRESS)->count())
                ->color('warning')
                ->icon('heroicon-o-arrow-path'),

            Stat::make('Overdue', Enquiry::where('direction', 'outbound')
                ->whereNotIn('status', [EnquiryStatus::CLOSED, EnquiryStatus::CONVERTED])
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count())
                ->color('danger')
                ->icon('heroicon-o-clock'),

            Stat::make('Closed This Month', Enquiry::where('status', EnquiryStatus::CLOSED)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count())
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
