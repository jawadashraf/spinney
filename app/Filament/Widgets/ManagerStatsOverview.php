<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnquiryStatus;
use App\Enums\SupportStatus;
use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\Enquiry;
use App\Models\ServiceUser;
use App\Models\Task;
use App\Models\ThirdPartyCarePlan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ManagerStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $needingAttentionCount = ServiceUser::query()
            ->whereHas('profile', fn ($query) => $query->whereIn('support_status', [
                SupportStatus::NeedsAttention,
                SupportStatus::UrgentAttention,
            ]))
            ->count();

        $safeguardingCount = Enquiry::query()
            ->where('safeguarding_flags', true)
            ->where('status', '!=', EnquiryStatus::CLOSED)
            ->count();

        $overdueTasksCount = Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        $activeCarePlansCount = ThirdPartyCarePlan::query()
            ->whereIn('status', [ThirdPartyCarePlanStatus::PENDING, ThirdPartyCarePlanStatus::IN_PROGRESS])
            ->count();

        return [
            Stat::make('Users Needing Support', (string) $needingAttentionCount)
                ->description('Service users flagged with support status')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($needingAttentionCount > 0 ? 'danger' : 'success'),

            Stat::make('Safeguarding Concerns', (string) $safeguardingCount)
                ->description('Unresolved enquiry safeguarding flags')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($safeguardingCount > 0 ? 'danger' : 'success'),

            Stat::make('Overdue Tasks', (string) $overdueTasksCount)
                ->description('Tasks past due date requiring attention')
                ->descriptionIcon('heroicon-o-clock')
                ->color($overdueTasksCount > 0 ? 'warning' : 'gray'),

            Stat::make('Active Care Plans', (string) $activeCarePlansCount)
                ->description('Pending & in-progress care plans')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),
        ];
    }
}
