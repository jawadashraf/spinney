<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\Outreach\CompaniesNotContactedWidget;
use App\Filament\Widgets\Outreach\DirectoryCoverageWidget;
use App\Filament\Widgets\Outreach\EngagementsPerMonthChart;
use App\Filament\Widgets\Outreach\OutreachEmailsPerMonthChart;
use App\Filament\Widgets\Outreach\OutreachStatsOverview;
use App\Models\Company;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Outreach programme engagement metrics: directory coverage, engagement over time and outreach emails.
 */
final class OutreachDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Outreach dashboard';

    protected static ?string $title = 'Outreach dashboard';

    protected static string $routePath = 'outreach';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }

    public function getWidgets(): array
    {
        return [
            OutreachStatsOverview::class,
            EngagementsPerMonthChart::class,
            OutreachEmailsPerMonthChart::class,
            DirectoryCoverageWidget::class,
            CompaniesNotContactedWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
