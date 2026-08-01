<?php

declare(strict_types=1);

use App\Enums\SupportStatus;
use App\Events\ServiceUserNeedsAttention;
use App\Models\Note;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ServiceUserNeedsAttentionNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->team = Team::firstOrCreate(['name' => 'Spinney Hill', 'personal_team' => false, 'user_id' => 1]);
    setPermissionsTeamId($this->team->id);

    $adminRole = Role::findOrCreate('admin', 'web');
    $managerRole = Role::findOrCreate('manager', 'web');

    $createNotePermission = Permission::findOrCreate('Create:Note', 'web');
    $adminRole->givePermissionTo($createNotePermission);

    $this->admin = User::factory()->create(['is_system_admin' => true, 'current_team_id' => $this->team->id]);
    $this->admin->assignRole($adminRole);

    $this->manager = User::factory()->create(['is_system_admin' => true, 'current_team_id' => $this->team->id]);
    $this->manager->assignRole($managerRole);

    $this->staff = User::factory()->create(['current_team_id' => $this->team->id]);

    Filament::setTenant($this->team, true);
});

it('sends service user needs attention notification to admin and manager roles when event is dispatched', function () {
    LaravelNotification::fake();

    $person = People::factory()->create([
        'type' => 'service_user',
        'is_service_user' => true,
        'team_id' => $this->team->id,
    ]);
    /** @var ServiceUser $serviceUser */
    $serviceUser = ServiceUser::find($person->id);
    $serviceUser->profile()->create([
        'team_id' => $this->team->id,
        'support_status' => SupportStatus::Normal,
    ]);

    $note = Note::factory()->create([
        'team_id' => $this->team->id,
        'support_status' => SupportStatus::NeedsAttention,
        'title' => 'Requires support check',
        'body' => 'Needs attention note details',
    ]);

    ServiceUserNeedsAttention::dispatch($serviceUser, $note, SupportStatus::NeedsAttention);

    LaravelNotification::assertSentTo(
        [$this->admin, $this->manager],
        ServiceUserNeedsAttentionNotification::class
    );

    LaravelNotification::assertNotSentTo(
        $this->staff,
        ServiceUserNeedsAttentionNotification::class
    );
});

it('includes correct notification details and action link', function () {
    LaravelNotification::fake();

    $person = People::factory()->create([
        'type' => 'service_user',
        'is_service_user' => true,
        'team_id' => $this->team->id,
    ]);
    /** @var ServiceUser $serviceUser */
    $serviceUser = ServiceUser::find($person->id);

    $note = Note::factory()->create([
        'team_id' => $this->team->id,
        'support_status' => SupportStatus::UrgentAttention,
        'title' => 'Urgent medical attention needed',
        'body' => 'Service user requires immediate review.',
    ]);

    $notification = new ServiceUserNeedsAttentionNotification($serviceUser, $note, SupportStatus::UrgentAttention);
    $mail = $notification->toMail($this->admin);

    expect($mail->subject)->toContain('Urgent Attention')
        ->and($mail->introLines[0])->toContain($serviceUser->name);

    $data = $notification->toArray($this->admin);
    expect($data['title'])->toContain($serviceUser->name);
});

it('dispatches ServiceUserNeedsAttention event when note needing attention is created for service user', function () {
    Event::fake([ServiceUserNeedsAttention::class]);

    $person = People::factory()->create([
        'type' => 'service_user',
        'is_service_user' => true,
        'team_id' => $this->team->id,
    ]);
    /** @var ServiceUser $serviceUser */
    $serviceUser = ServiceUser::find($person->id);
    $serviceUser->profile()->create([
        'team_id' => $this->team->id,
        'support_status' => SupportStatus::Normal,
    ]);

    $note = Note::factory()->create([
        'team_id' => $this->team->id,
        'support_status' => SupportStatus::UrgentAttention,
        'title' => 'Urgent check',
        'body' => 'Needs urgent attention',
    ]);

    event(new ServiceUserNeedsAttention($serviceUser, $note, SupportStatus::UrgentAttention));

    Event::assertDispatched(
        ServiceUserNeedsAttention::class,
        fn (ServiceUserNeedsAttention $event) => $event->serviceUser->id === $serviceUser->id && $event->status === SupportStatus::UrgentAttention
    );
});
