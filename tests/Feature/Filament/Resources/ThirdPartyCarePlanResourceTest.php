<?php
 
declare(strict_types=1);
 
use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\People;
use App\Models\Team;
use App\Models\ThirdPartyCarePlan;
use App\Models\User;
use Database\Seeders\ThirdPartyCarePlanCustomFieldSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
 
use App\Filament\Resources\ThirdPartyCarePlans\Pages\EditThirdPartyCarePlan;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;
 
beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->manager->assignRole('manager');
 
    // Grant permissions for ThirdPartyCarePlan to the manager role for testing
    $permissions = [
        'ViewAny:ThirdPartyCarePlan',
        'View:ThirdPartyCarePlan',
        'Create:ThirdPartyCarePlan',
        'Update:ThirdPartyCarePlan',
        'Delete:ThirdPartyCarePlan',
    ];
 
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $this->manager->givePermissionTo($permission);
    }
 
    $this->seed(ThirdPartyCarePlanCustomFieldSeeder::class);
    
    actingAs($this->manager);
    Filament::setTenant($this->team);
 
    $this->serviceUser = People::factory()->create(['is_service_user' => true, 'team_id' => $this->team->id]);
});
 
it('can list care plans for managers', function () {
    ThirdPartyCarePlan::factory()->count(3)->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);
 
    $response = get(route('filament.app.resources.third-party-care-plans.index', ['tenant' => $this->team]));
 
    $response->assertSuccessful();
});
 
it('can create care plan for managers', function () {
    $response = get(route('filament.app.resources.third-party-care-plans.create', ['tenant' => $this->team]));
 
    $response->assertSuccessful();
});
 
it('cannot create care plan for regular users', function () {
    $user = User::factory()->create(['current_team_id' => $this->team->id]);
 
    actingAs($user);
    Filament::setTenant($this->team);
 
    $response = get(route('filament.app.resources.third-party-care-plans.create', ['tenant' => $this->team]));
 
    $response->assertForbidden();
});
 
it('can view care plan details', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'creator_id' => $this->manager->id,
    ]);
 
    $response = get(route('filament.app.resources.third-party-care-plans.view', ['record' => $carePlan, 'tenant' => $this->team]));
 
    $response->assertSuccessful();
});
 
it('can filter care plans by status', function () {
    ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'status' => ThirdPartyCarePlanStatus::PENDING,
    ]);
 
    ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'status' => ThirdPartyCarePlanStatus::COMPLETED,
    ]);
 
    $response = get(route('filament.app.resources.third-party-care-plans.index', [
        'tenant' => $this->team,
        'tableFilters' => [
            'status' => ['value' => ThirdPartyCarePlanStatus::PENDING->value],
        ],
    ]));
 
    $response->assertSuccessful();
});
 
it('can filter care plans by service user', function () {
    ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);
 
    $otherUser = People::factory()->create(['is_service_user' => true, 'team_id' => $this->team->id]);
    ThirdPartyCarePlan::factory()->create([
        'people_id' => $otherUser->id,
        'team_id' => $this->team->id,
    ]);
 
    $response = get(route('filament.app.resources.third-party-care-plans.index', [
        'tenant' => $this->team,
        'tableFilters' => [
            'service_user' => ['value' => $this->serviceUser->id],
        ],
    ]));
 
    $response->assertSuccessful();
});
 
it('displays custom fields correctly on form', function () {
    $response = get(route('filament.app.resources.third-party-care-plans.create', ['tenant' => $this->team]));
 
    $response->assertSuccessful()
        ->assertSee('Treatment Goals')
        ->assertSee('Presenting Issues')
        ->assertSee('Risk Assessment');
});
 
it('can update assigned care plan for manager', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'creator_id' => $this->manager->id,
    ]);
 
    $carePlan->managers()->attach($this->manager->id, ['role' => 'primary_manager']);
 
    $response = get(route('filament.app.resources.third-party-care-plans.edit', ['record' => $carePlan, 'tenant' => $this->team]));
 
    $response->assertSuccessful();
});
 
it('generates correct status badge colors', function () {
    expect(ThirdPartyCarePlanStatus::PENDING->value)->toBe('pending')
        ->and(ThirdPartyCarePlanStatus::IN_PROGRESS->value)->toBe('in_progress')
        ->and(ThirdPartyCarePlanStatus::COMPLETED->value)->toBe('completed')
        ->and(ThirdPartyCarePlanStatus::CANCELLED->value)->toBe('cancelled');
});
 
it('belongs to service user', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);
 
    expect($carePlan->serviceUser)->toBeInstanceOf(People::class)
        ->and($carePlan->serviceUser->id)->toBe($this->serviceUser->id);
});
 
it('belongs to many managers', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);
 
    $carePlan->managers()->attach($this->manager->id, ['role' => 'primary_manager']);
 
    expect($carePlan->managers)->toHaveCount(1)
        ->and($carePlan->managers->first()->id)->toBe($this->manager->id);
});
 
it('casts status to enum', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'status' => ThirdPartyCarePlanStatus::IN_PROGRESS,
    ]);
 
    expect($carePlan->status)->toBeInstanceOf(ThirdPartyCarePlanStatus::class)
        ->and($carePlan->status)->toBe(ThirdPartyCarePlanStatus::IN_PROGRESS);
});
 
it('can check if it can be updated', function () {
    $pendingPlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'status' => ThirdPartyCarePlanStatus::PENDING,
    ]);
 
    $completedPlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
        'status' => ThirdPartyCarePlanStatus::COMPLETED,
    ]);
 
    expect($pendingPlan->canBeUpdated())->toBeTrue()
        ->and($completedPlan->canBeUpdated())->toBeFalse();
});

it('locks the service user field on edit', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);

    livewire(EditThirdPartyCarePlan::class, [
        'record' => $carePlan->getRouteKey(),
    ])
        ->assertSee('The service user cannot be changed once the care plan is created.');
});

it('can view the attachments relation manager', function () {
    $carePlan = ThirdPartyCarePlan::factory()->create([
        'people_id' => $this->serviceUser->id,
        'team_id' => $this->team->id,
    ]);

    livewire(EditThirdPartyCarePlan::class, [
        'record' => $carePlan->getRouteKey(),
    ])
        ->assertSee('Attachments');
});
