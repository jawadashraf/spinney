<?php

declare(strict_types=1);

use App\Filament\Resources\PhoneCalls\Pages\ListPhoneCalls;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Support\CallPermissions;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    CallPermissions::ensure($this->team->id);

    $this->manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->manager->assignRole('manager');

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');

    actingAs($this->manager);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);
});

it('shows a manager every call in the team, matched or not', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id]);
    $matched = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'people_id' => $serviceUser->id, 'user_id' => $this->liaison->id]);
    $unmatched = PbxCallLog::factory()->create(['team_id' => $this->team->id]);
    $otherTeam = PbxCallLog::factory()->create(['team_id' => Team::factory()->create()->id]);

    livewire(ListPhoneCalls::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$matched, $unmatched])
        ->assertCanNotSeeTableRecords([$otherTeam])
        ->assertTableColumnStateSet('contact', $serviceUser->name, $matched)
        ->assertSee(ServiceUserResource::getUrl('view', ['record' => $serviceUser->id]), escape: false);
});

it('shows a liaison only the calls they made or answered', function () {
    $own = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id]);
    $colleagues = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->manager->id]);
    $unmappedExtension = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => null]);

    actingAs($this->liaison);

    livewire(ListPhoneCalls::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$colleagues, $unmappedExtension]);
});

it('filters to calls matched to a contact', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $matched = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'people_id' => $person->id]);
    $unmatched = PbxCallLog::factory()->create(['team_id' => $this->team->id]);

    livewire(ListPhoneCalls::class)
        ->filterTable('matched', true)
        ->assertCanSeeTableRecords([$matched])
        ->assertCanNotSeeTableRecords([$unmatched])
        ->filterTable('matched', false)
        ->assertCanSeeTableRecords([$unmatched])
        ->assertCanNotSeeTableRecords([$matched]);
});

it('forbids users without access to calls', function () {
    actingAs(User::factory()->create(['current_team_id' => $this->team->id]));

    livewire(ListPhoneCalls::class)->assertForbidden();
});
