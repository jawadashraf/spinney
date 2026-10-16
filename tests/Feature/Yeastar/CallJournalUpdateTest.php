<?php

declare(strict_types=1);

use App\Models\PbxCallLog;
use App\Models\Team;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
});

it('stores the recording link the phone system sends after the call', function () {
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'recording_url' => null]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->putJson(route('yeastar.call-journal.update', $log), ['recording_url' => 'https://pbx.example.test/recordings/1791457200.101.wav'])
        ->assertOk()
        ->assertExactJson(['id' => (string) $log->id]);

    expect($log->refresh()->recording_url)->toBe('https://pbx.example.test/recordings/1791457200.101.wav');
});

it('keeps the existing recording link when the update has none', function () {
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'recording_url' => 'https://pbx.example.test/recordings/first.wav']);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->putJson(route('yeastar.call-journal.update', $log), ['recording_url' => ''])
        ->assertOk();

    expect($log->refresh()->recording_url)->toBe('https://pbx.example.test/recordings/first.wav');
});

it('returns 422 for a recording link longer than 2048 characters', function () {
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->putJson(route('yeastar.call-journal.update', $log), ['recording_url' => 'https://pbx.example.test/'.str_repeat('a', 2048)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['recording_url' => 'The recording url field must not be greater than 2048 characters.']);
});

it('returns 404 for a call logged for another team', function () {
    $log = PbxCallLog::factory()->create(['team_id' => Team::factory()->create()->id]);

    $this->withHeaders(yeastarHeaders($this->team->id))
        ->putJson(route('yeastar.call-journal.update', $log), ['recording_url' => 'https://pbx.example.test/recordings/x.wav'])
        ->assertNotFound();
});
