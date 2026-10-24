<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Enums\OutreachRecipientStatus;
use App\Filament\Pages\OutreachDashboard;
use App\Filament\Widgets\Outreach\CompaniesNotContactedWidget;
use App\Filament\Widgets\Outreach\DirectoryCoverageWidget;
use App\Filament\Widgets\Outreach\OutreachStatsOverview;
use App\Models\Company;
use App\Models\CompanyEngagement;
use App\Models\CompanyType;
use App\Models\OutreachEmail;
use App\Models\People;
use App\Models\Team;
use App\Support\OutreachMetrics;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->metrics = new OutreachMetrics($this->team->id);
});

function sentTo(Company $company, OutreachRecipientStatus $status, mixed $sentAt): void
{
    $email = OutreachEmail::factory()->for($company->team)->create();

    $email->recipients()->create([
        'team_id' => $company->team_id,
        'company_id' => $company->id,
        'email' => fake()->unique()->safeEmail(),
        'status' => $status,
        'sent_at' => $status === OutreachRecipientStatus::Sent ? $sentAt : null,
    ]);
}

describe('access', function () {
    it('shows the dashboard to managers', function () {
        signInToDirectoryAs($this->team, 'manager');

        $this->get(OutreachDashboard::getUrl())
            ->assertOk()
            ->assertSee('Outreach dashboard');
    });

    it('refuses staff without directory access', function () {
        signInToDirectoryAs($this->team, 'liaison');

        $this->get(OutreachDashboard::getUrl())->assertForbidden();
    });
});

describe('figures', function () {
    it('counts companies reachable by a usable general email or a mailing-list contact', function () {
        Company::factory()->for($this->team)->create(['email' => 'office@one.test']);
        $viaContact = Company::factory()->for($this->team)->create();
        People::factory()->professional()->create(['team_id' => $this->team->id, 'company_id' => $viaContact->id]);
        $offList = Company::factory()->for($this->team)->create();
        People::factory()->professional()->create(['team_id' => $this->team->id, 'company_id' => $offList->id, 'consent_communications' => false]);
        Company::factory()->for($this->team)->create(['email' => 'bounced@two.test', 'email_invalid_at' => now()]);
        Company::factory()->for($this->team)->create(['email' => 'left@three.test', 'unsubscribed_at' => now()]);
        Company::factory()->for($this->team)->create(['status' => CompanyStatus::Inactive, 'email' => 'old@four.test']);
        Company::factory()->create(['email' => 'other@team.test']);

        expect($this->metrics->directory())->toMatchArray(['active' => 5, 'inactive' => 1, 'reachable' => 2]);
    });

    it('counts verified companies and those needing checking', function () {
        Company::factory()->for($this->team)->create(['verification_status' => CompanyVerificationStatus::Verified]);
        Company::factory()->for($this->team)->count(2)->create(['verification_status' => CompanyVerificationStatus::NeedsChecking]);
        Company::factory()->for($this->team)->create();

        expect($this->metrics->directory())->toMatchArray(['verified' => 1, 'needs_checking' => 2]);
    });

    it('counts recent engagements and the companies they involved', function () {
        $company = Company::factory()->for($this->team)->create();
        CompanyEngagement::factory()->for($company)->count(2)->create(['team_id' => $this->team->id, 'occurred_on' => now()->subDays(10)]);
        CompanyEngagement::factory()->for(Company::factory()->for($this->team))->create(['team_id' => $this->team->id, 'occurred_on' => now()->subDays(89)]);
        CompanyEngagement::factory()->for($company)->create(['team_id' => $this->team->id, 'occurred_on' => now()->subDays(120)]);

        expect($this->metrics->engagementsInLastDays(90))->toBe(['engagements' => 3, 'companies' => 2]);
    });

    it('counts outreach sends and failures', function () {
        $company = Company::factory()->for($this->team)->create();
        sentTo($company, OutreachRecipientStatus::Sent, now());
        sentTo($company, OutreachRecipientStatus::Failed, null);

        expect($this->metrics->outreachInLastDays(30))->toBe(['sent' => 1, 'failed' => 1, 'emails' => 2]);
    });

    it('counts unsubscribed general addresses and contacts', function () {
        $company = Company::factory()->for($this->team)->create(['unsubscribed_at' => now()]);
        People::factory()->professional()->create(['team_id' => $this->team->id, 'company_id' => $company->id, 'unsubscribed_at' => now()]);

        expect($this->metrics->unsubscribed())->toBe(2);
    });

    it('groups engagements by month over the last year, including empty months', function () {
        $this->travelTo(now()->setDate(2026, 10, 16));
        $company = Company::factory()->for($this->team)->create();
        CompanyEngagement::factory()->for($company)->count(2)->create(['team_id' => $this->team->id, 'occurred_on' => '2026-10-02']);
        CompanyEngagement::factory()->for($company)->create(['team_id' => $this->team->id, 'occurred_on' => '2025-11-30']);
        CompanyEngagement::factory()->for($company)->create(['team_id' => $this->team->id, 'occurred_on' => '2025-10-31']);

        $months = $this->metrics->engagementsPerMonth();

        expect($months)->toHaveCount(12)
            ->and(array_key_first($months))->toBe('Nov 2025')
            ->and($months['Nov 2025'])->toBe(1)
            ->and($months['Oct 2026'])->toBe(2)
            ->and(array_sum($months))->toBe(3);
    });
});

describe('tables', function () {
    beforeEach(function () {
        signInToDirectoryAs($this->team, 'manager');
    });

    it('shows coverage per company type', function () {
        $masjid = CompanyType::factory()->for($this->team)->create(['name' => 'Masjid']);
        Company::factory()->for($this->team)->create(['company_type_id' => $masjid->id, 'email' => 'a@masjid.test', 'verification_status' => CompanyVerificationStatus::Verified]);
        Company::factory()->for($this->team)->create(['company_type_id' => $masjid->id]);

        livewire(DirectoryCoverageWidget::class)
            ->assertTableColumnStateSet('active_count', 2, $masjid)
            ->assertTableColumnStateSet('reachable_count', 1, $masjid)
            ->assertTableColumnStateSet('verified_count', 1, $masjid);
    });

    it('lists active companies not engaged or emailed in six months', function () {
        $never = Company::factory()->for($this->team)->create();
        $longAgo = Company::factory()->for($this->team)->create();
        CompanyEngagement::factory()->for($longAgo)->create(['team_id' => $this->team->id, 'occurred_on' => now()->subMonths(7)]);
        $recentlyEngaged = Company::factory()->for($this->team)->create();
        CompanyEngagement::factory()->for($recentlyEngaged)->create(['team_id' => $this->team->id, 'occurred_on' => now()->subMonth()]);
        $recentlyEmailed = Company::factory()->for($this->team)->create();
        sentTo($recentlyEmailed, OutreachRecipientStatus::Sent, now()->subWeek());
        $failedEmailOnly = Company::factory()->for($this->team)->create();
        sentTo($failedEmailOnly, OutreachRecipientStatus::Failed, null);
        $inactive = Company::factory()->for($this->team)->create(['status' => CompanyStatus::Inactive]);

        livewire(CompaniesNotContactedWidget::class)
            ->assertCanSeeTableRecords([$never, $longAgo, $failedEmailOnly])
            ->assertCanNotSeeTableRecords([$recentlyEngaged, $recentlyEmailed, $inactive]);
    });
});

describe('widget links', function () {
    beforeEach(function () {
        signInToDirectoryAs($this->team, 'manager');
    });

    it('renders outreach stats overview with resource urls', function () {
        $widget = new OutreachStatsOverview;
        $stats = (fn () => $this->getStats())->call($widget);

        expect($stats)->toHaveCount(6);
        expect($stats[0]->getUrl())->toContain('companies');
        expect($stats[1]->getUrl())->toContain('reachable_by_email');
        expect($stats[2]->getUrl())->toContain('verification_status');
        expect($stats[3]->getUrl())->toContain('companies');
        expect($stats[4]->getUrl())->toContain('outreach-emails');
        expect($stats[5]->getUrl())->toContain('unsubscribed');
    });

    it('renders directory coverage and companies not contacted with header actions', function () {
        livewire(DirectoryCoverageWidget::class)
            ->assertSuccessful()
            ->assertTableHeaderActionsExistInOrder(['view_all_companies']);

        livewire(CompaniesNotContactedWidget::class)
            ->assertSuccessful()
            ->assertTableHeaderActionsExistInOrder(['view_all_companies']);
    });
});
