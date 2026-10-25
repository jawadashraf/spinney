<?php

declare(strict_types=1);

use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Filament\Resources\ServiceUsers\Pages\ViewServiceUser;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $admin->assignRole('super_admin');

    actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);
});

it('shows the phone number as page text on the service user pages so Linkus can detect it', function (string $page) {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    livewire($page, ['record' => $serviceUser->id])
        ->assertOk()
        ->assertSee('Phone: +447438356414');
})->with([
    'edit page' => EditServiceUser::class,
    'view page' => ViewServiceUser::class,
]);

it('shows no phone line for a service user without a number', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => null]);

    livewire(EditServiceUser::class, ['record' => $serviceUser->id])
        ->assertOk()
        ->assertDontSee('Phone: ');
});

it('lists service users with their phone number', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    livewire(ListServiceUsers::class)
        ->assertTableColumnStateSet('phone', '+447438356414', $serviceUser->id);
});
