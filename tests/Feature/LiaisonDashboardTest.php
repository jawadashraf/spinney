<?php

declare(strict_types=1);

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Filament\Pages\LiaisonDashboard;
use App\Models\Enquiry;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function createSuperAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

it('can render the liaison dashboard', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows correct enquiry stats for open enquiries', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->count(3)->create([
        'status' => EnquiryStatus::OPEN,
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows overdue calls in widget', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->create([
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
        'due_date' => now()->subDay(),
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows upcoming calls in widget', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->create([
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
        'due_date' => now()->addDay(),
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows safeguarding alerts in widget', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows emergency calls in safeguarding widget', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->create([
        'call_type' => EnquiryCallType::EMERGENCY,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows recent enquiries in widget', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    Enquiry::factory()->count(5)->create([
        'user_id' => $user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

use App\Filament\Widgets\ActiveCarePlansWidget;
use App\Filament\Widgets\EnquiriesByCategoryChart;
use App\Filament\Widgets\EnquiryStatsOverview;
use App\Filament\Widgets\RecentEnquiriesWidget;
use App\Filament\Widgets\UpcomingCallsWidget;
use App\Filament\Widgets\UpcomingCarePlanReferralsWidget;

it('renders enquiry stats overview with filtered resource urls', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    $component = livewire(EnquiryStatsOverview::class)
        ->assertSuccessful();

    $stats = (fn () => $this->getCachedStats())->call($component->instance());
    expect($stats)->toHaveCount(4);
    expect($stats[0]->getUrl())->toContain('tableFilters');
    expect($stats[0]->getUrl())->toContain('open');
    expect($stats[1]->getUrl())->toContain('tableFilters');
    expect($stats[1]->getUrl())->toContain('in_progress');
    expect($stats[2]->getUrl())->toContain('tableFilters');
    expect($stats[2]->getUrl())->toContain('overdue');
    expect($stats[3]->getUrl())->toContain('tableFilters');
    expect($stats[3]->getUrl())->toContain('closed');
});

it('renders liaison table and chart widgets successfully', function () {
    $user = createSuperAdminUser();
    actingAs($user);

    livewire(UpcomingCallsWidget::class)->assertSuccessful();
    livewire(RecentEnquiriesWidget::class)->assertSuccessful();
    livewire(UpcomingCarePlanReferralsWidget::class)->assertSuccessful();
    livewire(ActiveCarePlansWidget::class)->assertSuccessful();
    livewire(EnquiriesByCategoryChart::class)->assertSuccessful();
});
