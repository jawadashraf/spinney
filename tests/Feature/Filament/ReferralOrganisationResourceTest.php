<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ReferralPriority;
use App\Filament\Resources\ReferralOrganisations\Pages\ListReferralOrganisations;
use App\Filament\Resources\ReferralOrganisations\ReferralOrganisationResource;
use App\Http\Middleware\EnsureVolunteerWorkHours;
use App\Livewire\ReferralDirectoryLookup;
use App\Models\ReferralOrganisation;
use App\Models\Team;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

describe('access & authorization', function () {
    it('allows volunteer liaisons, liaisons, managers, and admins to open the directory', function (string $role) {
        if ($role === 'volunteer_liaison') {
            $this->withoutMiddleware(EnsureVolunteerWorkHours::class);
        }

        signInToReferralsAs($this->team, $role);

        $this->get(ReferralOrganisationResource::getUrl('index'))
            ->assertOk();
    })->with(['volunteer_liaison', 'liaison', 'manager', 'admin']);

    it('denies access to roles without referral permissions', function () {
        signInToDirectoryAs($this->team, 'service_user');

        $this->get(ReferralOrganisationResource::getUrl('index'))
            ->assertForbidden();
    });

    it('grants view-only to volunteer liaisons and full management to managers/admins', function (string $role, bool $canManage) {
        $user = signInToReferralsAs($this->team, $role);

        expect($user->can('viewAny', ReferralOrganisation::class))->toBeTrue()
            ->and($user->can('create', ReferralOrganisation::class))->toBe($canManage)
            ->and($user->can('update', ReferralOrganisation::class))->toBe($canManage)
            ->and($user->can('delete', ReferralOrganisation::class))->toBe($canManage);
    })->with([
        'volunteer_liaison' => ['volunteer_liaison', false],
        'liaison' => ['liaison', false],
        'manager' => ['manager', true],
        'admin' => ['admin', true],
    ]);
});

describe('table & directory search', function () {
    it('renders organisations in the table', function () {
        signInToReferralsAs($this->team, 'volunteer_liaison');

        $org = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Leicester Shelter Test Service',
            'area_of_support' => 'Housing support',
            'priority' => ReferralPriority::Urgent,
        ]);

        livewire(ListReferralOrganisations::class)
            ->assertCanSeeTableRecords([$org]);
    });

    it('searches organisations by name and area of support', function () {
        signInToReferralsAs($this->team, 'volunteer_liaison');

        $org1 = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Alpha Domestic Abuse Help',
            'area_of_support' => 'Domestic abuse',
        ]);

        $org2 = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Beta Food Depot',
            'area_of_support' => 'Food banks',
        ]);

        livewire(ListReferralOrganisations::class)
            ->searchTable('Alpha')
            ->assertCanSeeTableRecords([$org1])
            ->assertCanNotSeeTableRecords([$org2]);
    });

    it('filters organisations by priority', function () {
        signInToReferralsAs($this->team, 'volunteer_liaison');

        $emergency = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Emergency 999 Centre',
            'priority' => ReferralPriority::Emergency,
        ]);

        $routine = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Routine Advice Centre',
            'priority' => ReferralPriority::Routine,
        ]);

        livewire(ListReferralOrganisations::class)
            ->filterTable('priority', ReferralPriority::Emergency->value)
            ->assertCanSeeTableRecords([$emergency])
            ->assertCanNotSeeTableRecords([$routine]);
    });
});

describe('livewire referral directory lookup component', function () {
    it('filters organisations by live search in lookup component', function () {
        signInToReferralsAs($this->team, 'volunteer_liaison');

        ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Specific Test Foodbank',
            'area_of_support' => 'Food banks',
            'is_active' => true,
        ]);

        ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Unrelated Mental Health Clinic',
            'area_of_support' => 'Mental health',
            'is_active' => true,
        ]);

        livewire(ReferralDirectoryLookup::class)
            ->set('search', 'Foodbank')
            ->assertSee('Specific Test Foodbank')
            ->assertDontSee('Unrelated Mental Health Clinic');
    });
});

describe('phone normalisation', function () {
    it('normalises phone numbers upon saving', function () {
        $org = ReferralOrganisation::factory()->create([
            'team_id' => $this->team->id,
            'telephone' => '0116 254 5168',
        ]);

        expect($org->phone_normalized)->toBe('+441162545168');
    });
});
