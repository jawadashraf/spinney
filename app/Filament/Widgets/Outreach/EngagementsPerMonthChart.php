<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Outreach;

use App\Models\Company;
use App\Support\OutreachMetrics;
use Filament\Widgets\ChartWidget;

final class EngagementsPerMonthChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Engagements per month';

    protected ?string $description = 'Visits, talks, events and meetings logged on company pages, last 12 months.';

    protected function getData(): array
    {
        $months = OutreachMetrics::forCurrentTeam()->engagementsPerMonth();

        return [
            'datasets' => [
                [
                    'label' => 'Engagements',
                    'data' => array_values($months),
                    'borderRadius' => 4,
                    'maxBarThickness' => 28,
                ],
            ],
            'labels' => array_keys($months),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return OutreachChartOptions::singleSeriesCounts();
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }
}
