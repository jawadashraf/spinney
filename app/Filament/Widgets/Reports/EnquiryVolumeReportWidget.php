<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\ReportPermissions;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Enquiry volume and category aggregate report (AC RP-001, RP-005).
 * Shows aggregate enquiry counts by category, status, and handling team member,
 * reactive to date range, category, and status filters.
 */
final class EnquiryVolumeReportWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.reports.enquiry-volume-report';

    public static function canView(): bool
    {
        return ReportPermissions::canViewEnquiryReport(auth()->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $teamId = Filament::getTenant()?->getKey() ?? auth()->user()?->current_team_id;

        $baseQuery = Enquiry::query()
            ->when($teamId !== null, fn (Builder $q) => $q->where('team_id', $teamId));

        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;
        $categoryFilter = $this->pageFilters['category'] ?? null;
        $statusFilter = $this->pageFilters['status'] ?? null;

        $filteredQuery = (clone $baseQuery)
            ->when($startDate, fn (Builder $q, string $date) => $q->whereDate('occurred_at', '>=', $date))
            ->when($endDate, fn (Builder $q, string $date) => $q->whereDate('occurred_at', '<=', $date))
            ->when($categoryFilter, fn (Builder $q, string $category) => $q->where('category', $category))
            ->when($statusFilter, fn (Builder $q, string $status) => $q->where('status', $status));

        $totalEnquiries = (clone $filteredQuery)->count();
        $safeguardingCount = (clone $filteredQuery)->where('safeguarding_flags', true)->count();
        $openCount = (clone $filteredQuery)->where('status', EnquiryStatus::OPEN)->count();
        $closedCount = (clone $filteredQuery)->whereIn('status', [EnquiryStatus::CLOSED, EnquiryStatus::CONVERTED])->count();

        // Count by Category
        $categoryCounts = (clone $filteredQuery)
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $byCategory = [];
        foreach (EnquiryCategory::cases() as $case) {
            $count = (int) ($categoryCounts[$case->value] ?? 0);
            $percentage = $totalEnquiries > 0 ? round(($count / $totalEnquiries) * 100, 1) : 0.0;
            $byCategory[] = [
                'case' => $case,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // Count by Status
        $statusCounts = (clone $filteredQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byStatus = [];
        foreach (EnquiryStatus::cases() as $case) {
            $count = (int) ($statusCounts[$case->value] ?? 0);
            $percentage = $totalEnquiries > 0 ? round(($count / $totalEnquiries) * 100, 1) : 0.0;
            $byStatus[] = [
                'case' => $case,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // Count by Handling Team Member (Staff)
        $staffStats = (clone $filteredQuery)
            ->selectRaw('user_id, count(*) as total, sum(case when status = ? then 1 else 0 end) as open_count, sum(case when status in (?, ?) then 1 else 0 end) as resolved_count', [
                EnquiryStatus::OPEN->value,
                EnquiryStatus::CLOSED->value,
                EnquiryStatus::CONVERTED->value,
            ])
            ->groupBy('user_id')
            ->get();

        $usersById = User::query()
            ->whereIn('id', $staffStats->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id')
            ->all();

        $byStaff = $staffStats->map(function ($row) use ($usersById, $totalEnquiries): array {
            $name = $row->user_id ? ($usersById[$row->user_id] ?? 'Unknown Staff') : 'Unassigned';
            $count = (int) $row->total;
            $percentage = $totalEnquiries > 0 ? round(($count / $totalEnquiries) * 100, 1) : 0.0;

            return [
                'user_id' => $row->user_id,
                'staff' => $name,
                'total' => $count,
                'open' => (int) $row->open_count,
                'resolved' => (int) $row->resolved_count,
                'percentage' => $percentage,
            ];
        })->sortByDesc('total')->values()->all();

        return [
            'totalEnquiries' => $totalEnquiries,
            'safeguardingCount' => $safeguardingCount,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'byCategory' => $byCategory,
            'byStatus' => $byStatus,
            'byStaff' => $byStaff,
            'dateRangeLabel' => $startDate && $endDate
                ? "{$startDate} to {$endDate}"
                : ($startDate ? "Since {$startDate}" : ($endDate ? "Up to {$endDate}" : 'All Time')),
        ];
    }
}
