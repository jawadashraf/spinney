<?php

declare(strict_types=1);

use App\Models\Call;
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
            'id' => (string) $serviceUser->id,
            'first_name' => 'Jawad',
            'last_name' => 'Ashraf',
            'phone' => '+447438356414',
            'url' => route('filament.app.resources.service-users.edit', ['tenant' => $this->team->slug, 'record' => $serviceUser->id]),
        ]]]);
});

it('matches a caller whose number the phone system sends in national format', function () {
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id, 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '07438356414']))
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $serviceUser->id);
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
        ->assertJsonPath('data.0.url', route('filament.app.resources.service-users.edit', ['tenant' => $this->team->slug, 'record' => $serviceUser->id]));
});

it('returns no contacts for an unknown number', function () {
    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not match people who are not service users', function () {
    People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

it('does not match service users from another team', function () {
    People::factory()->serviceUser()->create(['team_id' => Team::factory()->create()->id, 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.contacts.lookup', ['phone' => '+447438356414']))
        ->assertOk()
        ->assertExactJson(['data' => []]);
});
