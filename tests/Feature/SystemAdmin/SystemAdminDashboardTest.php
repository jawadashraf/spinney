<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\SystemAdmin\Filament\Resources\CompanyResource;
use Relaticle\SystemAdmin\Filament\Resources\OpportunityResource;
use Relaticle\SystemAdmin\Filament\Resources\TaskResource;
use Relaticle\SystemAdmin\Filament\Widgets\BusinessOverviewWidget;
use Relaticle\SystemAdmin\Filament\Widgets\TeamPerformanceTableWidget;

use function Pest\Livewire\livewire;

describe('SystemAdmin Dashboard Widgets', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('sysadmin'));
        $admin = User::factory()->create(['is_system_admin' => true]);
        $this->actingAs($admin, 'web');
    });

    it('renders business overview widget with target resource urls', function () {
        $widget = new BusinessOverviewWidget;
        $stats = (fn () => $this->getStats())->call($widget);

        expect($stats)->toHaveCount(4);
        expect($stats[0]->getUrl())->toBe(OpportunityResource::getUrl('index'));
        expect($stats[1]->getUrl())->toBe(OpportunityResource::getUrl('index'));
        expect($stats[2]->getUrl())->toBe(TaskResource::getUrl('index'));
        expect($stats[3]->getUrl())->toBe(CompanyResource::getUrl('index'));
    });

    it('renders team performance table widget with header action and column urls', function () {
        livewire(TeamPerformanceTableWidget::class)
            ->assertSuccessful()
            ->assertTableHeaderActionsExistInOrder(['view_all_users']);
    });
});
