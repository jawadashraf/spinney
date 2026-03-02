<?php

declare(strict_types=1);

use App\Models\Donor;
use App\Models\People;
use App\Models\Professional;
use App\Models\Relative;
use App\Models\ServiceUser;
use App\Models\User;

it('uses STI to create specialized people types', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->personalTeam();

    $serviceUser = ServiceUser::create([
        'name' => 'John Service',
        'email' => 'john@example.com',
        'team_id' => $team->id,
    ]);

    $donor = Donor::create([
        'name' => 'Jane Donor',
        'email' => 'jane@example.com',
        'team_id' => $team->id,
    ]);

    expect($serviceUser)->toBeInstanceOf(ServiceUser::class);
    expect($serviceUser->type)->toBe('service_user');

    expect($donor)->toBeInstanceOf(Donor::class);
    expect($donor->type)->toBe('donor');

    $allPeople = People::all();
    expect($allPeople)->toHaveCount(2);
    expect($allPeople->first())->toBeInstanceOf(ServiceUser::class);
    expect($allPeople->last())->toBeInstanceOf(Donor::class);
});

it('can link people through relationships', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->personalTeam();

    $person = People::create(['name' => 'Parent Person', 'team_id' => $team->id]);
    $relative = People::create(['name' => 'Relative Person', 'team_id' => $team->id]);

    $person->relatedPeople()->attach($relative->id, [
        'relation_type' => 'Brother',
        'is_emergency_contact' => true,
    ]);

    expect($person->relatedPeople()->get())->toHaveCount(1);
    expect($person->relatedPeople->first()->name)->toBe('Relative Person');
    expect($person->relatedPeople->first()->pivot->relation_type)->toBe('Brother');
    expect($person->relatedPeople->first()->pivot->is_emergency_contact)->toBe(1);

    // Verify reverse relationship
    expect($relative->relatedBy()->get())->toHaveCount(1);
    expect($relative->relatedBy->first()->name)->toBe('Parent Person');
});

it('filters people by type in tabs', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->personalTeam();

    ServiceUser::create(['name' => 'S1', 'team_id' => $team->id]);
    Donor::create(['name' => 'D1', 'team_id' => $team->id]);
    Relative::create(['name' => 'R1', 'team_id' => $team->id]);
    Professional::create(['name' => 'P1', 'team_id' => $team->id]);

    expect(People::where('type', 'service_user')->count())->toBe(1);
    expect(People::where('type', 'donor')->count())->toBe(1);
    expect(People::where('type', 'relative')->count())->toBe(1);
    expect(People::where('type', 'professional')->count())->toBe(1);
});
