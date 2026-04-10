<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('assigns current team_id to note on creation', function () {
    $team = Team::factory()->create();
    $user = User::factory()->withPersonalTeam()->create([
        'current_team_id' => $team->id,
    ]);

    actingAs($user);

    $note = Note::create([
        'title' => 'Test Note',
    ]);

    expect($note->team_id)->toBe($team->id)
        ->and($note->creator_id)->toBe($user->id);
});
