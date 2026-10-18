<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Enums\SupportStatus;
use App\Models\ServiceUserProfile;
use App\Support\ReportPermissions;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service user engagement aggregate report (AC RP-002, RP-005).
 * Shows service user count by service team and engagement status distribution,
 * reactive to date range, service team, and engagement status filters.
 */
final class ServiceUserEngagementReportWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.reports.service-user-engagement-report';

    public static function canView(): bool
    {
        return ReportPermissions::canViewEngagementReport(auth()->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $teamId = Filament::getTenant()?->getKey() ?? auth()->user()?->current_team_id;

        $baseQuery = ServiceUserProfile::query()
            ->when($teamId !== null, fn (Builder $q) => $q->where('team_id', $teamId));

        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;
        $teamFilter = $this->pageFilters['target_service_team'] ?? null;
        $statusFilter = $this->pageFilters['engagement_status'] ?? null;

        $filteredQuery = (clone $baseQuery)
            ->when($startDate, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
            ->when($endDate, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))
            ->when($teamFilter, fn (Builder $q, string $team) => $q->where('target_service_team', $team))
            ->when($statusFilter, fn (Builder $q, string $status) => $q->where('engagement_status', $status));

        $totalServiceUsers = (clone $filteredQuery)->count();
        $activeCount = (clone $filteredQuery)->where('engagement_status', EngagementStatus::ACTIVE)->count();
        $pendingCount = (clone $filteredQuery)->where('engagement_status', EngagementStatus::PENDING)->count();
        $dischargedCount = (clone $filteredQuery)->where('engagement_status', EngagementStatus::DISCHARGED)->count();
        $needsAttentionCount = (clone $filteredQuery)
            ->whereIn('support_status', [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention])
            ->count();

        // Distribution by Target Service Team
        $teamCounts = (clone $filteredQuery)
            ->selectRaw('target_service_team, count(*) as count')
            ->groupBy('target_service_team')
            ->pluck('count', 'target_service_team')
            ->toArray();

        $byServiceTeam = [];
        foreach (ServiceTeam::cases() as $case) {
            $count = (int) ($teamCounts[$case->value] ?? 0);
            $percentage = $totalServiceUsers > 0 ? round(($count / $totalServiceUsers) * 100, 1) : 0.0;
            $byServiceTeam[] = [
                'case' => $case,
                'label' => $case->getLabel(),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // Distribution by Engagement Status
        $statusCounts = (clone $filteredQuery)
            ->selectRaw('engagement_status, count(*) as count')
            ->groupBy('engagement_status')
            ->pluck('count', 'engagement_status')
            ->toArray();

        $byEngagementStatus = [];
        foreach (EngagementStatus::cases() as $case) {
            $count = (int) ($statusCounts[$case->value] ?? 0);
            $percentage = $totalServiceUsers > 0 ? round(($count / $totalServiceUsers) * 100, 1) : 0.0;
            $byEngagementStatus[] = [
                'case' => $case,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'totalServiceUsers' => $totalServiceUsers,
            'activeCount' => $activeCount,
            'pendingCount' => $pendingCount,
            'dischargedCount' => $dischargedCount,
            'needsAttentionCount' => $needsAttentionCount,
            'byServiceTeam' => $byServiceTeam,
            'byEngagementStatus' => $byEngagementStatus,
            'dateRangeLabel' => $startDate && $endDate
                ? "{$startDate} to {$endDate}"
                : ($startDate ? "Since {$startDate}" : ($endDate ? "Up to {$endDate}" : 'All Time')),
        ];
    }
}
