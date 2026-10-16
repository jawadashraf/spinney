<?php

declare(strict_types=1);

use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('returns 401 when the request has no credentials', function () {
    yeastarHeaders($this->team->id);

    $this->getJson(route('yeastar.users.index'))
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('returns 401 for a wrong api key', function () {
    yeastarHeaders($this->team->id);

    $this->withHeader('Authorization', 'Basic '.base64_encode('not-the-key'))
        ->getJson(route('yeastar.users.index'))
        ->assertUnauthorized();
});

it('returns 401 when no api key is configured', function () {
    $headers = yeastarHeaders($this->team->id);
    config(['services.yeastar.api_key' => null]);

    $this->withHeaders($headers)
        ->getJson(route('yeastar.users.index'))
        ->assertUnauthorized();
});

it('returns 401 when the configured team does not exist', function () {
    $headers = yeastarHeaders(teamId: 999_999);

    $this->withHeaders($headers)
        ->getJson(route('yeastar.users.index'))
        ->assertUnauthorized();
});

it('accepts the api key sent as a basic auth password', function () {
    yeastarHeaders($this->team->id);

    $this->withHeader('Authorization', 'Basic '.base64_encode('spinney:test-yeastar-key'))
        ->getJson(route('yeastar.users.index'))
        ->assertOk();
});
