<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\People;
use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('acknowledges an unknown number without creating a person or company', function () {
    $peopleBefore = People::query()->count();
    $companiesBefore = Company::query()->count();

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.contacts.unknown'), ['first_name' => '07438356414', 'last_name' => '', 'phone' => '07438 356414'])
        ->assertOk()
        ->assertExactJson(['id' => 'unknown-447438356414']);

    expect(People::query()->count())->toBe($peopleBefore)
        ->and(Company::query()->count())->toBe($companiesBefore);
});

it('returns the existing contact when the number has since been added to a record', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative', 'phone' => '+447438356414']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.contacts.unknown'), ['phone' => '07438356414'])
        ->assertOk()
        ->assertExactJson(['id' => 'person-'.$relative->id]);
});

it('acknowledges a withheld number', function () {
    $this->withHeaders(yeastarHeaders($this->team->id))
        ->postJson(route('yeastar.contacts.unknown'), ['phone' => ''])
        ->assertOk()
        ->assertExactJson(['id' => 'unknown-withheld']);
});

it('returns 401 when the phone system sends no credentials', function () {
    yeastarHeaders($this->team->id);

    $this->postJson(route('yeastar.contacts.unknown'), ['phone' => '07438356414'])
        ->assertUnauthorized();
});
