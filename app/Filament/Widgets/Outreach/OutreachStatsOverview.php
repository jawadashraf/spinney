<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Outreach;

use App\Models\Company;
use App\Support\OutreachMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class OutreachStatsOverview extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $metrics = OutreachMetrics::forCurrentTeam();
        $directory = $metrics->directory();
        $engagements = $metrics->engagementsInLastDays(90);
        $outreach = $metrics->outreachInLastDays(30);

        $reachablePercent = $directory['active'] > 0 ? (int) round($directory['reachable'] / $directory['active'] * 100) : 0;

        return [
            Stat::make('Companies in the directory', (string) $directory['active'])
                ->description("{$directory['inactive']} inactive")
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),
            Stat::make('Reachable by email', "{$reachablePercent}%")
                ->description("{$directory['reachable']} of {$directory['active']} companies")
                ->descriptionIcon('heroicon-o-envelope')
                ->color($reachablePercent >= 50 ? 'success' : 'warning'),
            Stat::make('Verified by staff', (string) $directory['verified'])
                ->description("{$directory['needs_checking']} need checking")
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($directory['needs_checking'] > 0 ? 'warning' : 'success'),
            Stat::make('Engagements (90 days)', (string) $engagements['engagements'])
                ->description("With {$engagements['companies']} companies")
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Outreach emails sent (30 days)', (string) $outreach['sent'])
                ->description("In {$outreach['emails']} emails · {$outreach['failed']} failed")
                ->descriptionIcon('heroicon-o-paper-airplane')
                ->color($outreach['failed'] > 0 ? 'danger' : 'info'),
            Stat::make('Unsubscribed addresses', (string) $metrics->unsubscribed())
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }
}
