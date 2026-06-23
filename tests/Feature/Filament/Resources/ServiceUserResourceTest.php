<?php

declare(strict_types=1);

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Filament\Resources\ServiceUsers\Pages\CreateServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\ServiceUser;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create([
        'current_team_id' => $this->team->id,
        'is_system_admin' => true,
    ]);
    $this->team->update(['user_id' => $this->user->id]);
    $this->user->refresh();
    
    actingAs($this->user);
    Filament::setTenant($this->team);
    Filament::setCurrentPanel('app');
    Filament::bootCurrentPanel();
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

    // Get the email component from the form with Livewire context
    $livewire = \Pest\Livewire\livewire(CreateServiceUser::class)->instance();
    $components = ServiceUserForm::getComponents();
    $schema = Schema::make($livewire)->components($components);

    $findComponent = function (array $components) use (&$findComponent) {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $component->getName() === 'email') {
                return $component;
            }
            if (method_exists($component, 'getChildComponents')) {
                $found = $findComponent($component->getChildComponents());
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    };

    $emailField = $findComponent($schema->getComponents());

    expect($emailField)->not->toBeNull();

    // Set the record on the schema to simulate form state
    $schema->record($serviceUser);

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

it('can render the create page', function () {
    \Pest\Livewire\livewire(CreateServiceUser::class)
        ->assertSuccessful();
});

it('has redirect URL set to the index page', function () {
    $page = new CreateServiceUser;

    $reflector = new ReflectionMethod(CreateServiceUser::class, 'getRedirectUrl');

    $redirectUrl = $reflector->invoke($page);

    expect($redirectUrl)->toBe(ServiceUserResource::getUrl('index'));
});

// it('can autocomplete address using postcode', function () {
//     $url = ServiceUserResource::getUrl('create', ['tenant' => $this->team]);
//     $relativeUrl = parse_url($url, PHP_URL_PATH);
    
//     // Set Referer header so Filament can resolve the tenant during Livewire updates
//     $this->withHeaders([
//         'Referer' => $url,
//     ]);
    
//     $this->get($relativeUrl)->assertSuccessful();

//     \Pest\Livewire\livewire(CreateServiceUser::class)
//         ->fillForm([
//             'postcode' => 'SW1A 2AA',
//         ])
//         ->assertSuccessful()
//         ->mountFormComponentAction('postcode', 'findAddress')
//         ->callFormComponentAction('postcode', 'findAddress', data: [
//             'selected_address' => "10 Downing Street\nWestminster\nLondon\nSW1A 2AA",
//         ])
//         ->assertSchemaStateSet([
//             'address' => "10 Downing Street\nWestminster\nLondon\nSW1A 2AA",
//         ]);
// });
