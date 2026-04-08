<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\ThirdPartyCarePlan;
use Filament\Widgets\ChartWidget;

final class ActiveCarePlansWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Care Plans by Status';
    }

    protected function getData(): array
    {
        $pending = ThirdPartyCarePlan::where('status', ThirdPartyCarePlanStatus::PENDING)->count();
        $inProgress = ThirdPartyCarePlan::where('status', ThirdPartyCarePlanStatus::IN_PROGRESS)->count();
        $completed = ThirdPartyCarePlan::where('status', ThirdPartyCarePlanStatus::COMPLETED)->count();
        $cancelled = ThirdPartyCarePlan::where('status', ThirdPartyCarePlanStatus::CANCELLED)->count();

        return [
            'datasets' => [
                [
                    'data' => [$pending, $inProgress, $completed, $cancelled],
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                ],
            ],
            'labels' => ['Pending', 'In Progress', 'Completed', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('ViewAny:ThirdPartyCarePlan');
    }
}
