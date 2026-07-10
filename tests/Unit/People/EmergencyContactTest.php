<?php

declare(strict_types=1);

use App\Models\People;
use App\Models\Team;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can sync an emergency contact relationship', function () {
    $team = Team::factory()->create();
    $person = People::factory()->create(['team_id' => $team->id]);
    $emergencyContact = People::factory()->create(['team_id' => $team->id]);

    $person->syncEmergencyContact($emergencyContact->id, 'mother');

    assertDatabaseHas('person_relationships', [
        'person_id' => $person->id,
        'related_person_id' => $emergencyContact->id,
        'is_emergency_contact' => true,
        'relation_type' => 'mother',
        'team_id' => $team->id,
    ]);

    expect($person->emergencyContacts()->first()->id)->toBe($emergencyContact->id);
});

it('can clear an emergency contact relationship', function () {
    $team = Team::factory()->create();
    $person = People::factory()->create(['team_id' => $team->id]);
    $emergencyContact = People::factory()->create(['team_id' => $team->id]);

    $person->syncEmergencyContact($emergencyContact->id, 'mother');
    $person->syncEmergencyContact(null);

    assertDatabaseMissing('person_relationships', [
        'person_id' => $person->id,
        'related_person_id' => $emergencyContact->id,
        'is_emergency_contact' => true,
    ]);

    expect($person->emergencyContacts()->first())->toBeNull();
});

it('replaces the existing emergency contact when syncing a new one', function () {
    $team = Team::factory()->create();
    $person = People::factory()->create(['team_id' => $team->id]);
    $firstContact = People::factory()->create(['team_id' => $team->id]);
    $secondContact = People::factory()->create(['team_id' => $team->id]);

    $person->syncEmergencyContact($firstContact->id, 'mother');
    $person->syncEmergencyContact($secondContact->id, 'spouse');

    assertDatabaseMissing('person_relationships', [
        'person_id' => $person->id,
        'related_person_id' => $firstContact->id,
        'is_emergency_contact' => true,
    ]);

    assertDatabaseHas('person_relationships', [
        'person_id' => $person->id,
        'related_person_id' => $secondContact->id,
        'is_emergency_contact' => true,
        'relation_type' => 'spouse',
        'team_id' => $team->id,
    ]);

    expect($person->emergencyContacts()->first()->id)->toBe($secondContact->id);
});

it('does not create a relationship when only a relation type is provided', function () {
    $team = Team::factory()->create();
    $person = People::factory()->create(['team_id' => $team->id]);

    $person->syncEmergencyContact(null, 'mother');

    assertDatabaseMissing('person_relationships', [
        'person_id' => $person->id,
        'is_emergency_contact' => true,
    ]);

    expect($person->emergencyContacts()->first())->toBeNull();
});
