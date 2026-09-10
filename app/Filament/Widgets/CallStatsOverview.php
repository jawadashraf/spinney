<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Models\CallAttempt;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CallStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $dueToday = CallResource::getEloquentQuery()->dueToday()->count();
        $overdue = CallResource::getEloquentQuery()->overdue()->count();

        $completedThisWeek = CallResource::getEloquentQuery()
            ->where('calls.status', CallStatus::Completed)
            ->where('calls.completed_at', '>=', now()->startOfWeek())
            ->count();

        $missedThisMonth = CallResource::getEloquentQuery()
            ->where('calls.status', CallStatus::Missed)
            ->where('calls.updated_at', '>=', now()->subDays(30))
            ->count();

        $attempts = CallAttempt::query()
            ->whereIn('call_id', CallResource::getEloquentQuery()->select('calls.id'))
            ->where('attempted_at', '>=', now()->subDays(30));

        $totalAttempts = (clone $attempts)->count();
        $answeredAttempts = (clone $attempts)->where('outcome', CallOutcome::Answered)->count();
        $contactRate = $totalAttempts > 0 ? (int) round($answeredAttempts / $totalAttempts * 100) : 0;

        return [
            Stat::make('Calls due today', (string) $dueToday)
                ->descriptionIcon('heroicon-o-calendar')
                ->description('Still to be made today')
                ->color('info'),
            Stat::make('Overdue calls', (string) $overdue)
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->description('Past their due time')
                ->color($overdue > 0 ? 'danger' : 'success'),
            Stat::make('Completed this week', (string) $completedThisWeek)
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Missed (30 days)', (string) $missedThisMonth)
                ->description('Unreachable after all attempts')
                ->descriptionIcon('heroicon-o-phone-x-mark')
                ->color($missedThisMonth > 0 ? 'warning' : 'gray'),
            Stat::make('Contact rate (30 days)', "{$contactRate}%")
                ->description("{$answeredAttempts} of {$totalAttempts} attempts answered")
                ->color($contactRate >= 70 ? 'success' : 'warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Call::class) ?? false;
    }
}
