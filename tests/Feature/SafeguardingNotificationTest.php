<?php

declare(strict_types=1);

use App\Enums\EnquiryCallType;
use App\Events\SafeguardingFlagRaised;
use App\Models\Enquiry;
use App\Models\Team;
use App\Models\User;
use App\Notifications\SafeguardingNotification;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $team = Team::where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($team->id);

    $adminRole = Role::findOrCreate('admin', 'web');
    $managerRole = Role::findOrCreate('manager', 'web');

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);

    $this->manager = User::factory()->create();
    $this->manager->assignRole($managerRole);

    $this->staff = User::factory()->create();
});

it('sends safeguarding notification to admin and manager roles', function () {
    LaravelNotification::fake();

    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    SafeguardingFlagRaised::dispatch($enquiry);

    LaravelNotification::assertSentTo(
        [$this->admin, $this->manager],
        SafeguardingNotification::class
    );

    LaravelNotification::assertNotSentTo(
        $this->staff,
        SafeguardingNotification::class
    );
});

it('dispatches safeguarding flag raised event when enquiry is created with safeguarding flags', function () {
    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    expect($enquiry->safeguarding_flags)->toBeTrue();
});

it('auto-sets safeguarding flags for emergency call type', function () {
    $enquiry = Enquiry::factory()->create([
        'call_type' => EnquiryCallType::EMERGENCY,
        'safeguarding_flags' => false,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    expect($enquiry->fresh()->safeguarding_flags)->toBeTrue();
});

it('does not send notification to users without admin or manager role', function () {
    LaravelNotification::fake();

    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    SafeguardingFlagRaised::dispatch($enquiry);

    LaravelNotification::assertNotSentTo(
        $this->staff,
        SafeguardingNotification::class
    );
});

it('notification contains correct enquiry data', function () {
    LaravelNotification::fake();

    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'reason_for_contact' => 'Urgent safeguarding concern',
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    SafeguardingFlagRaised::dispatch($enquiry);

    LaravelNotification::assertSentTo(
        $this->admin,
        function (SafeguardingNotification $notification) use ($enquiry): bool {
            return $notification->enquiry->id === $enquiry->id;
        }
    );
});

it('observer dispatches event on enquiry create with safeguarding flags', function () {
    LaravelNotification::fake();

    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    LaravelNotification::assertSentTo(
        [$this->admin, $this->manager],
        SafeguardingNotification::class
    );
});

it('observer dispatches event when safeguarding flags toggled on', function () {
    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => false,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    LaravelNotification::fake();

    $enquiry->update(['safeguarding_flags' => true]);

    LaravelNotification::assertSentTo(
        [$this->admin, $this->manager],
        SafeguardingNotification::class
    );
});

it('does not dispatch event when safeguarding flags toggled off', function () {
    $enquiry = Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'team_id' => Team::where('name', 'Spinney Hill')->first()->id,
    ]);

    LaravelNotification::fake();

    $enquiry->update(['safeguarding_flags' => false]);

    LaravelNotification::assertNotSentTo($this->admin, SafeguardingNotification::class);
    LaravelNotification::assertNotSentTo($this->manager, SafeguardingNotification::class);
});
