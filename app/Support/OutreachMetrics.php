<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Enums\OutreachRecipientStatus;
use App\Models\Company;
use App\Models\CompanyEngagement;
use App\Models\OutreachEmailRecipient;
use App\Models\People;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

/**
 * Figures for the outreach dashboard, always for one team.
 */
final readonly class OutreachMetrics
{
    public function __construct(private int $teamId) {}

    public static function forCurrentTeam(): self
    {
        return new self((int) Filament::getTenant()?->getKey());
    }

    /**
     * @return array{active: int, inactive: int, reachable: int, verified: int, needs_checking: int}
     */
    public function directory(): array
    {
        $active = Company::query()->where('team_id', $this->teamId)->where('status', CompanyStatus::Active);

        return [
            'active' => (clone $active)->count(),
            'inactive' => Company::query()->where('team_id', $this->teamId)->where('status', CompanyStatus::Inactive)->count(),
            'reachable' => (clone $active)->reachableByEmail()->count(),
            'verified' => (clone $active)->where('verification_status', CompanyVerificationStatus::Verified)->count(),
            'needs_checking' => (clone $active)->where('verification_status', CompanyVerificationStatus::NeedsChecking)->count(),
        ];
    }

    /**
     * @return array{engagements: int, companies: int}
     */
    public function engagementsInLastDays(int $days): array
    {
        $engagements = CompanyEngagement::query()
            ->where('team_id', $this->teamId)
            ->where('occurred_on', '>=', now()->subDays($days)->startOfDay());

        return [
            'engagements' => (clone $engagements)->count(),
            'companies' => (clone $engagements)->distinct()->count('company_id'),
        ];
    }

    /**
     * Addresses emailed (sent successfully), failed sends, and distinct outreach emails behind them.
     *
     * @return array{sent: int, failed: int, emails: int}
     */
    public function outreachInLastDays(int $days): array
    {
        $recipients = OutreachEmailRecipient::query()
            ->where('team_id', $this->teamId)
            ->where('created_at', '>=', now()->subDays($days));

        return [
            'sent' => (clone $recipients)->where('status', OutreachRecipientStatus::Sent)->count(),
            'failed' => (clone $recipients)->where('status', OutreachRecipientStatus::Failed)->count(),
            'emails' => (clone $recipients)->distinct()->count('outreach_email_id'),
        ];
    }

    /**
     * Company general addresses and contacts that have unsubscribed.
     */
    public function unsubscribed(): int
    {
        return Company::query()->where('team_id', $this->teamId)->whereNotNull('unsubscribed_at')->count()
            + People::query()->where('team_id', $this->teamId)->where('type', 'professional')->whereNotNull('unsubscribed_at')->count();
    }

    /**
     * Engagements logged per month, oldest first, including empty months.
     *
     * @return array<string, int> keyed by month label, e.g. "Oct 2026"
     */
    public function engagementsPerMonth(int $months = 12): array
    {
        $dates = CompanyEngagement::query()
            ->where('team_id', $this->teamId)
            ->where('occurred_on', '>=', $this->firstMonth($months))
            ->pluck('occurred_on');

        return $this->countByMonth($dates, $months);
    }

    /**
     * Outreach addresses emailed successfully per month, oldest first, including empty months.
     *
     * @return array<string, int> keyed by month label, e.g. "Oct 2026"
     */
    public function outreachSendsPerMonth(int $months = 12): array
    {
        $dates = OutreachEmailRecipient::query()
            ->where('team_id', $this->teamId)
            ->where('status', OutreachRecipientStatus::Sent)
            ->where('sent_at', '>=', $this->firstMonth($months))
            ->pluck('sent_at');

        return $this->countByMonth($dates, $months);
    }

    private function firstMonth(int $months): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfMonth()->subMonthsNoOverflow($months - 1);
    }

    /**
     * @param  Collection<int, mixed>  $dates
     * @return array<string, int>
     */
    private function countByMonth(Collection $dates, int $months): array
    {
        $counts = $dates
            ->map(fn (mixed $date): string => CarbonImmutable::parse($date)->format('Y-m'))
            ->countBy();

        $series = [];

        for ($month = $this->firstMonth($months); $month->lessThanOrEqualTo(now()); $month = $month->addMonthNoOverflow()) {
            $series[$month->format('M Y')] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return $series;
    }
}
