<?php

declare(strict_types=1);

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
        ->assertJsonPath('data.0.id', (string) $serviceUser->id);
});

it('finds service users by part of their number typed in national format', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => '07438 356']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $serviceUser->id);
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

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => '%%']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not search service users from another team', function () {
    People::factory()->serviceUser()->create(['team_id' => Team::factory()->create()->id, 'first_name' => 'Jawad']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.search', ['q' => 'Jawad']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});
