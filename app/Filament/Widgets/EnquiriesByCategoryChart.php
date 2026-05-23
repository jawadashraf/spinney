<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Filament\Widgets\ChartWidget;

final class EnquiriesByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Enquiries by Category';
    }

    protected function getData(): array
    {
        $data = Enquiry::whereNotIn('status', [EnquiryStatus::CLOSED, EnquiryStatus::CONVERTED])
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $labels = [];
        $values = [];

        foreach (EnquiryCategory::cases() as $case) {
            $labels[] = $case->getLabel();
            $values[] = $data[$case->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => array_map(
                        fn (EnquiryCategory $case) => match ($case->getColor()) {
                            'info' => '#3b82f6',
                            'primary' => '#6366f1',
                            'warning' => '#f59e0b',
                            'success' => '#10b981',
                            'danger' => '#ef4444',
                            default => '#6b7280',
                        },
                        EnquiryCategory::cases(),
                    ),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
