<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\People;
use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('finds service users by part of their name', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'first_name' => 'Jawad', 'last_name' => 'Ashraf']);
    People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'first_name' => 'Mary', 'last_name' => 'Jones']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'ashr']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'person-'.$serviceUser->id);
});

it('finds people of every type and companies, listing service users first', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id, 'name' => 'Ashraf Trust']);
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'first_name' => 'Aisha', 'last_name' => 'Ashraf']);
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'first_name' => 'Jawad', 'last_name' => 'Ashraf']);

    $response = $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'ashraf']))
        ->assertOk();

    expect($response->json('data.*.id'))->toBe([
        'person-'.$serviceUser->id,
        'person-'.$relative->id,
        'company-'.$company->id,
    ]);
});

it('finds contacts by part of their number typed in national format', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);
    $company = Company::factory()->create(['team_id' => $this->team->id, 'phone' => '07438 356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => '07438 356']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', 'person-'.$serviceUser->id)
        ->assertJsonPath('data.1.id', 'company-'.$company->id);
});

it('returns at most twenty contacts', function () {
    People::factory()->count(15)->serviceUser()->create(['team_id' => $this->team->id, 'last_name' => 'Ashraf']);
    Company::factory()->count(10)->create(['team_id' => $this->team->id, 'name' => 'Ashraf Trust']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'ashraf']))
        ->assertOk()
        ->assertJsonCount(20, 'data');
});

it('returns no contacts for a search shorter than two characters', function () {
    People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'first_name' => 'Jawad']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'J']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('treats wildcard characters in the search as literal text', function () {
    People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'first_name' => 'Jawad']);
    Company::factory()->create(['team_id' => $this->team->id, 'name' => 'Jawad Trust']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => '%%']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not search people or companies from another team', function () {
    $otherTeam = Team::factory()->create();
    People::factory()->serviceUser()->create(['team_id' => $otherTeam->id, 'first_name' => 'Jawad']);
    Company::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Jawad Trust']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'Jawad']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});
