<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Models\Department;
use App\Models\Enquiry;
use Filament\Widgets\ChartWidget;

final class DepartmentWorkloadChart extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Open Enquiries by Department';
    }

    protected function getData(): array
    {
        $departments = Department::withCount(['enquiries' => function ($query): void {
            $query->whereIn('status', [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS]);
        }])->get();

        $labels = $departments->pluck('name')->toArray();
        $values = $departments->pluck('enquiries_count')->toArray();

        $unassignedCount = Enquiry::whereIn('status', [EnquiryStatus::OPEN, EnquiryStatus::IN_PROGRESS])
            ->whereNull('department_id')
            ->count();

        $labels[] = 'Unassigned';
        $values[] = $unassignedCount;

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('ViewAny:Enquiry') ?? false;
    }
}
