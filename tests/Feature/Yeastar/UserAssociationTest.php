<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
});

it('lists the users who take liaison calls so extensions can be mapped to them', function () {
    $liaison = User::factory()->create([
        'current_team_id' => $this->team->id,
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@spinney.test',
    ]);
    $liaison->assignRole('liaison');

    $volunteerLiaison = User::factory()->create(['current_team_id' => $this->team->id, 'first_name' => 'Ben', 'last_name' => 'Ward']);
    $volunteerLiaison->assignRole(Role::firstOrCreate(['name' => 'volunteer_liaison', 'team_id' => $this->team->id], ['guard_name' => 'web']));

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.users.index'))
        ->assertOk()
        ->assertJsonFragment([
            'id' => (string) $liaison->id,
            'first_name' => 'Amina',
            'last_name' => 'Khan',
            'email' => 'amina@spinney.test',
        ])
        ->assertJsonFragment(['id' => (string) $volunteerLiaison->id]);
});

it('does not list users without a call role', function () {
    $user = User::factory()->create(['current_team_id' => $this->team->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.users.index'))
        ->assertOk()
        ->assertJsonMissing(['id' => (string) $user->id]);
});

it('does not list liaisons from another team', function () {
    $otherTeam = Team::factory()->create();
    setPermissionsTeamId($otherTeam->id);

    $otherLiaison = User::factory()->create(['current_team_id' => $otherTeam->id]);
    $otherLiaison->assignRole(Role::firstOrCreate(['name' => 'liaison', 'team_id' => $otherTeam->id], ['guard_name' => 'web']));

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->getJson(route('yeastar.users.index'))
        ->assertOk()
        ->assertJsonMissing(['id' => (string) $otherLiaison->id]);
});
