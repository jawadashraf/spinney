<?php

declare(strict_types=1);

use App\Models\Call;
use App\Models\Company;
use App\Models\People;
use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('matches a caller to a service user whose number is stored in national format', function () {
    $serviceUser = People::factory()->serviceUser()->create([
        'team_id' => $this->team->id,
        'first_name' => 'Jawad',
        'last_name' => 'Ashraf',
        'phone' => '07438 356414',
    ]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => [[
            'id' => 'person-'.$serviceUser->id,
            'first_name' => 'Jawad',
            'last_name' => 'Ashraf',
            'phone' => '+447438356414',
            'url' => route('filament.app.resources.service-users.view', ['tenant' => $this->team->slug, 'record' => $serviceUser->id]),
        ]]]);
});

it('matches a caller whose number the phone system sends in national format', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '07438356414']))
        ->assertOk()
        ->assertJsonPath('data.0.id', 'person-'.$serviceUser->id);
});

it('pops the call that is due today when the service user has one', function () {
    $this->travelTo(now()->setTime(9, 0));

    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);
    $dueCall = Call::factory()->create(['team_id' => $this->team->id, 'people_id' => $serviceUser->id, 'due_at' => now()->setTime(11, 0)]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertJsonPath('data.0.url', route('filament.app.resources.calls.view', ['tenant' => $this->team->slug, 'record' => $dueCall->id]));
});

it('pops the service user when their next call is not due until a later day', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);
    Call::factory()->create(['team_id' => $this->team->id, 'people_id' => $serviceUser->id, 'due_at' => now()->addDays(3)]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertJsonPath('data.0.url', route('filament.app.resources.service-users.view', ['tenant' => $this->team->slug, 'record' => $serviceUser->id]));
});

it('matches people who are not service users and pops their record', function (string $type) {
    $person = People::factory()->create(['team_id' => $this->team->id, 'type' => $type, 'phone' => '07438 356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'person-'.$person->id)
        ->assertJsonPath('data.0.url', route('filament.app.resources.people.view', ['tenant' => $this->team->slug, 'record' => $person->id]));
})->with([
    'relative' => 'relative',
    'professional' => 'professional',
    'donor' => 'donor',
]);

it('matches a company by its main number and pops the company', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id, 'name' => 'Spinney Hill Masjid', 'phone' => '07438 356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => [[
            'id' => 'company-'.$company->id,
            'first_name' => 'Spinney Hill Masjid',
            'last_name' => null,
            'phone' => '+447438356414',
            'url' => route('filament.app.resources.companies.view', ['tenant' => $this->team->slug, 'record' => $company->id]),
        ]]]);
});

it('lists service users first, then other people, then companies when they share a number', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '+447438356414']);
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    $response = $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk();

    expect($response->json('data.*.id'))->toBe([
        'person-'.$serviceUser->id,
        'person-'.$relative->id,
        'company-'.$company->id,
    ]);
});

it('returns no contacts for an unknown number', function () {
    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not match people or companies from another team', function () {
    $otherTeam = Team::factory()->create();
    People::factory()->serviceUser()->create(['team_id' => $otherTeam->id, 'phone' => '+447438356414']);
    Company::factory()->create(['team_id' => $otherTeam->id, 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});
