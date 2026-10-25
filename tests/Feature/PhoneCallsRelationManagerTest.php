<?php

declare(strict_types=1);

use App\Enums\PbxCallResult;
use App\Filament\Resources\Calls\RelationManagers\PhoneCallsRelationManager;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\Resources\ServiceUsers\Pages\ViewServiceUser;
use App\Models\Call;
use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);

    $this->admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->admin->assignRole('admin');

    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);
});

it('lists a service user phone calls with the liaison call they updated', function () {
    $serviceUser = ServiceUser::query()->findOrFail(People::factory()->serviceUser()->create(['team_id' => $this->team->id])->id);
    $call = Call::factory()->create(['team_id' => $this->team->id, 'people_id' => $serviceUser->id]);
    $log = PbxCallLog::factory()->create([
        'team_id' => $this->team->id,
        'people_id' => $serviceUser->id,
        'call_id' => $call->id,
        'user_id' => $this->admin->id,
        'recording_url' => 'https://pbx.example.test/recordings/1791457200.101.wav',
        'result' => PbxCallResult::AwaitingNotes,
    ]);
    $otherLog = PbxCallLog::factory()->create(['team_id' => $this->team->id]);

    livewire(PhoneCallsRelationManager::class, [
        'ownerRecord' => $serviceUser,
        'pageClass' => ViewServiceUser::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$log])
        ->assertCanNotSeeTableRecords([$otherLog])
        ->assertTableColumnVisible('result')
        ->assertSee('04:00')
        ->assertSee('https://pbx.example.test/recordings/1791457200.101.wav');
});

it('lists a relative phone calls without the liaison call column', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative']);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'people_id' => $relative->id]);

    livewire(PhoneCallsRelationManager::class, [
        'ownerRecord' => $relative,
        'pageClass' => ViewPeople::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$log])
        ->assertTableColumnHidden('result');
});

it('lists a company phone calls', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'company_id' => $company->id]);

    livewire(PhoneCallsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => ViewCompany::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$log]);
});

it('does not let anyone add, edit or delete phone calls', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'company_id' => $company->id]);

    livewire(PhoneCallsRelationManager::class, [
        'ownerRecord' => $company,
        'pageClass' => ViewCompany::class,
    ])
        ->assertTableActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit', record: $log)
        ->assertTableActionDoesNotExist('delete', record: $log);
});

it('shows the phone calls tab to a liaison on a service user record', function () {
    $liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $liaison->assignRole('liaison');
    actingAs($liaison);

    $serviceUser = ServiceUser::query()->findOrFail(People::factory()->serviceUser()->create(['team_id' => $this->team->id])->id);

    expect(PhoneCallsRelationManager::canViewForRecord($serviceUser, ViewServiceUser::class))->toBeTrue();
});
