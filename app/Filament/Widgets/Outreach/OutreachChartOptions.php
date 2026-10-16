<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Outreach;

/**
 * Shared Chart.js options for the outreach dashboard's single-series count charts: no legend (the heading
 * names the series), whole-number y axis from zero, recessive grid.
 */
final class OutreachChartOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function singleSeriesCounts(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'grid' => ['color' => 'rgba(148, 163, 184, 0.15)'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
