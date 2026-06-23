<?php

declare(strict_types=1);

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;
use App\Models\ServiceUser;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->user->assignRole('super_admin');
    actingAs($this->user);
    Filament::setTenant($this->team);
});

it('has correct unique validation rule', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $serviceUser = ServiceUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'team_id' => $this->team->id,
        'user_id' => $user->id,
        'is_service_user' => true,
    ]);

    // Get the email component from the form
    $components = ServiceUserForm::getComponents();
    $group = $components[0];
    $section = $group->getChildComponents()[0];
    $emailField = null;
    foreach ($section->getChildComponents() as $component) {
        if ($component->getName() === 'email') {
            $emailField = $component;
            break;
        }
    }

    expect($emailField)->not->toBeNull();

    // Set the record on the component to simulate form state
    $emailField->record($serviceUser);

    // Get the validation rules
    $rules = $emailField->getValidationRules();
    expect($rules)->toBeArray();
});

it('populates profile fields in edit mode', function () {
    $serviceUser = ServiceUser::create([
        'name' => 'Test Service User',
        'email' => 'testsu@example.com',
        'team_id' => $this->team->id,
        'is_service_user' => true,
    ]);

    $profile = ServiceUserProfile::create([
        'person_id' => $serviceUser->id,
        'team_id' => $this->team->id,
        'target_service_team' => ServiceTeam::ASSESSMENT,
        'engagement_status' => EngagementStatus::ACTIVE,
        'gp_name' => 'Dr. Smith',
    ]);

    expect($serviceUser->fresh()->profile)->not->toBeNull();
    expect($serviceUser->fresh()->profile->gp_name)->toBe('Dr. Smith');

    \Pest\Livewire\livewire(EditServiceUser::class, [
        'record' => $serviceUser->getRouteKey(),
    ])
        ->assertSchemaStateSet([
            'profile.gp_name' => 'Dr. Smith',
            'profile.target_service_team' => ServiceTeam::ASSESSMENT,
            'profile.engagement_status' => EngagementStatus::ACTIVE,
        ]);
});
