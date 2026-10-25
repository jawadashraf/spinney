<?php

declare(strict_types=1);

use App\Enums\EnquiryDirection;
use App\Filament\Resources\Enquiries\Pages\CreateEnquiry;
use App\Filament\Resources\UnmatchedCalls\Pages\ListUnmatchedCalls;
use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use App\Support\CallPermissions;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

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

it('lists only calls that are not linked to anyone', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $unmatched = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414']);
    $linked = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'people_id' => $person->id]);
    $otherTeam = PbxCallLog::factory()->create(['team_id' => Team::factory()->create()->id]);

    actingAs($this->manager);

    livewire(ListUnmatchedCalls::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$unmatched])
        ->assertCanNotSeeTableRecords([$linked, $otherTeam]);
});

it('shows a liaison only the unmatched calls they handled', function () {
    $own = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id]);
    $someoneElses = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->manager->id]);

    actingAs($this->liaison);

    livewire(ListUnmatchedCalls::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$someoneElses]);
});

it('forbids users without access to calls', function () {
    $user = User::factory()->create(['current_team_id' => $this->team->id]);

    actingAs($user);

    livewire(ListUnmatchedCalls::class)->assertForbidden();
});

it('links every unmatched call from the number to a person and saves the number on them', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => null]);
    $call = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414']);
    $earlierCall = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'contact_number' => '+447438356414', 'started_at' => now()->subWeek()]);

    actingAs($this->manager);

    livewire(ListUnmatchedCalls::class)
        ->callAction(TestAction::make('linkToContact')->table($call), data: [
            'contact' => 'person-'.$relative->id,
            'save_number' => true,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('2 calls linked to '.$relative->name);

    expect($call->fresh()->people_id)->toBe($relative->id)
        ->and($earlierCall->fresh()->people_id)->toBe($relative->id)
        ->and($relative->fresh()->phone_normalized)->toBe('+447438356414');
});

it('links a call to a company for a manager', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $call = PbxCallLog::factory()->create(['team_id' => $this->team->id]);

    actingAs($this->manager);

    livewire(ListUnmatchedCalls::class)
        ->callAction(TestAction::make('linkToContact')->table($call), data: ['contact' => 'company-'.$company->id, 'save_number' => false])
        ->assertHasNoFormErrors();

    expect($call->fresh()->company_id)->toBe($company->id);
});

it('does not let a liaison link a call to a company', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $call = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id]);

    actingAs($this->liaison);

    livewire(ListUnmatchedCalls::class)
        ->callAction(TestAction::make('linkToContact')->table($call), data: ['contact' => 'company-'.$company->id, 'save_number' => false])
        ->assertHasFormErrors(['contact']);

    expect($call->fresh()->company_id)->toBeNull();
});

it('dismisses a call by deleting its log', function () {
    $call = PbxCallLog::factory()->create(['team_id' => $this->team->id]);

    actingAs($this->manager);

    livewire(ListUnmatchedCalls::class)
        ->callAction(TestAction::make('dismiss')->table($call));

    expect(PbxCallLog::query()->find($call->id))->toBeNull();
});

it('starts an enquiry pre-filled from the call and links the call to it', function () {
    $admin = User::factory()->create(['current_team_id' => $this->team->id]);
    $admin->assignRole('super_admin');
    actingAs($admin);

    // Boot the panel so Filament assigns the team to the new enquiry, then restore the permissions team it resets.
    Filament::bootCurrentPanel();
    Filament::setTenant($this->team);
    setPermissionsTeamId($this->team->id);
    $this->manager->teams()->syncWithoutDetaching([$this->team->id => ['role' => 'admin']]);

    $call = PbxCallLog::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $admin->id,
        'direction' => PbxCallLog::DIRECTION_INBOUND,
        'contact_number' => '+447438356414',
        'started_at' => now()->subHour()->startOfMinute(),
    ]);

    Livewire::withQueryParams(['pbx_call' => $call->id])
        ->test(CreateEnquiry::class)
        ->assertSchemaStateSet([
            'phone' => '+447438356414',
            'direction' => EnquiryDirection::INBOUND,
            'occurred_at' => $call->started_at->format('Y-m-d H:i'),
        ])
        ->fillForm(['category' => 'family_advice', 'user_id' => $this->manager->id, 'reason_for_contact' => 'Asked about the lunch club.'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($call->fresh()->enquiry_id)->not->toBeNull()
        ->and($call->fresh()->enquiry->phone)->toBe('+447438356414');
});
