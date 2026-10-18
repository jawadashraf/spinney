<?php

declare(strict_types=1);

use App\Actions\Appointments\BookAppointment;
use App\Actions\Appointments\UpdateAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\Pages\ListServiceUsers;
use App\Filament\Resources\ServiceUsers\RelationManagers\ServiceUserAppointmentsRelationManager;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Filament\Widgets\CounsellorSessionsWidget;
use App\Models\People;
use App\Models\Schedule;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentReminderNotification;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Zap\Facades\Zap;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function counsellorIn(Team $team): User
{
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->syncWithoutDetaching([$team->id => ['role' => 'member']]);
    $user->assignRole('counselor');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function serviceUserIn(Team $team, array $attributes = []): People
{
    return People::factory()->serviceUser()->create([
        'team_id' => $team->id,
        'email' => 'amira@example.org',
        'consent_communications' => true,
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $metadata
 */
function bookWith(User $counsellor, ?People $serviceUser, string $date, array $metadata = []): Schedule
{
    return app(BookAppointment::class)->handle(
        schedulable: $counsellor,
        date: $date,
        startTime: '10:00',
        endTime: '11:00',
        name: 'Counselling session',
        metadata: $serviceUser !== null
            ? ['attendee_type' => AttendeeType::SERVICE_USER->value, 'service_user_id' => $serviceUser->id, 'counselor_type' => CounselorType::SPIRITUAL->value, ...$metadata]
            : ['counselor_type' => CounselorType::ASSESSMENT->value, ...$metadata],
        teamId: (int) $counsellor->current_team_id,
    );
}

/**
 * Book an appointment that took place the given number of days ago. Bookings cannot be made in the past,
 * so the clock is moved back to make it, then restored.
 */
function bookDaysAgo(User $counsellor, ?People $serviceUser, int $daysAgo): Schedule
{
    $previous = Carbon::getTestNow();
    Carbon::setTestNow(now()->subDays($daysAgo + 1));

    try {
        return bookWith($counsellor, $serviceUser, now()->addDay()->toDateString());
    } finally {
        Carbon::setTestNow($previous);
    }
}

function availabilityFor(User $counsellor, string $date, ?bool $approved): Schedule
{
    /** @var Schedule */
    return Zap::for($counsellor)
        ->named('Office hours')
        ->availability()
        ->from($date)
        ->addPeriod('09:00', '12:00')
        ->withMetadata($approved === null ? ['team_id' => $counsellor->current_team_id] : ['is_approved' => $approved])
        ->save();
}

describe('booking', function () {
    it('records the slot and emails the counsellor and a consenting service user', function () {
        Notification::fake();
        $counsellor = counsellorIn($this->team);
        $serviceUser = serviceUserIn($this->team);

        $appointment = bookWith($counsellor, $serviceUser, now()->addWeek()->toDateString());

        expect($appointment->getAppointmentStatus())->toBe(AppointmentStatus::SCHEDULED)
            ->and($appointment->team_id)->toBe($this->team->id)
            ->and($appointment->timeRange())->toBe('10:00 - 11:00')
            ->and($appointment->periods)->toHaveCount(1);

        Notification::assertSentTo($counsellor, AppointmentBookedNotification::class);
        Notification::assertSentOnDemand(AppointmentBookedNotification::class, fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => array_key_exists('amira@example.org', $notifiable->routes['mail']));
    });

    it('does not email a service user without consent or with a placeholder address', function (array $attributes) {
        Notification::fake();

        bookWith(counsellorIn($this->team), serviceUserIn($this->team, $attributes), now()->addWeek()->toDateString());

        Notification::assertNothingSentTo(new AnonymousNotifiable);
        Notification::assertSentOnDemandTimes(AppointmentBookedNotification::class, 0);
    })->with([
        'no consent' => [['consent_communications' => false]],
        'placeholder email' => [['email' => 'temp_abc123@spinney.local']],
    ]);

    it('emails an external attendee at their own address', function () {
        Notification::fake();

        bookWith(counsellorIn($this->team), null, now()->addWeek()->toDateString(), [
            'attendee_type' => AttendeeType::EXTERNAL->value,
            'external_attendee_name' => 'Imam Yusuf',
            'external_attendee_email' => 'yusuf@masjid.test',
        ]);

        Notification::assertSentOnDemand(AppointmentBookedNotification::class, fn ($notification, array $channels, AnonymousNotifiable $notifiable): bool => array_key_exists('yusuf@masjid.test', $notifiable->routes['mail']));
    });

    it('gives staff a link to the appointment but not the attendee', function () {
        Notification::fake();
        $counsellor = counsellorIn($this->team);
        $appointment = bookWith($counsellor, serviceUserIn($this->team), now()->addWeek()->toDateString());
        $notification = new AppointmentBookedNotification($appointment);

        $staffMail = $notification->toMail($counsellor);
        $attendeeMail = $notification->toMail(new AnonymousNotifiable);

        expect($staffMail->actionUrl)->toBe(AppointmentResource::getUrl('view', ['record' => $appointment], tenant: $this->team))
            ->and($attendeeMail->actionUrl)->toBeNull()
            ->and(implode(' ', $attendeeMail->introLines))->not->toContain('Attendee');
    });

    it('books from the service user page through the same path', function () {
        Notification::fake();
        $manager = signInToDirectoryAs($this->team, 'manager');
        foreach (['ViewAny:Schedule', 'Create:Schedule'] as $permission) {
            $manager->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $counsellor = counsellorIn($this->team);
        $serviceUser = serviceUserIn($this->team);
        $date = now()->addWeek()->next('Monday')->toDateString();
        availabilityFor($counsellor, $date, approved: true);

        livewire(ServiceUserAppointmentsRelationManager::class, ['ownerRecord' => ServiceUser::query()->findOrFail($serviceUser->id), 'pageClass' => EditServiceUser::class])
            ->callAction(TestAction::make('create')->table(), [
                'schedulable_id' => $counsellor->id,
                'counselor_type' => CounselorType::DRUG->value,
                'booking_date' => $date,
                'selected_slot' => '10:00-11:00',
                'name' => 'First session',
            ])
            ->assertHasNoActionErrors();

        $appointment = Schedule::query()->where('name', 'First session')->sole();

        expect($appointment->getCounselorType())->toBe(CounselorType::DRUG)
            ->and($appointment->timeRange())->toBe('10:00 - 11:00')
            ->and((int) $appointment->metadata['service_user_id'])->toBe($serviceUser->id);
        Notification::assertSentTo($counsellor, AppointmentBookedNotification::class);
    });
});

describe('status changes', function () {
    beforeEach(function () {
        Notification::fake();
        $this->counsellor = counsellorIn($this->team);
        $this->appointment = bookWith($this->counsellor, serviceUserIn($this->team), now()->addWeek()->toDateString());
        Notification::fake();
    });

    it('cancels, deactivates the booking, audits it and tells everyone, with the reason for staff only', function () {
        app(UpdateAppointmentStatus::class)->cancel($this->appointment, $this->counsellor, 'Counsellor unwell');

        $appointment = $this->appointment->refresh();

        expect($appointment->getAppointmentStatus())->toBe(AppointmentStatus::CANCELLED)
            ->and($appointment->is_active)->toBeFalse()
            ->and(Activity::query()->where('subject_id', $appointment->id)->where('description', 'Appointment cancelled')->exists())->toBeTrue();

        Notification::assertSentTo($this->counsellor, AppointmentCancelledNotification::class);
        Notification::assertSentOnDemand(AppointmentCancelledNotification::class);

        $notification = new AppointmentCancelledNotification($appointment);
        expect(implode(' ', $notification->toMail($this->counsellor)->introLines))->toContain('Counsellor unwell')
            ->and(implode(' ', $notification->toMail(new AnonymousNotifiable)->introLines))->not->toContain('Counsellor unwell');
    });

    it('records the session without copying the note into the audit log', function () {
        app(UpdateAppointmentStatus::class)->complete($this->appointment, $this->counsellor, AttendanceStatus::ATTENDED, 'Discussed relapse triggers.', 'Follow-up in two weeks');

        $appointment = $this->appointment->refresh();
        $activity = Activity::query()->where('subject_id', $appointment->id)->latest('id')->firstOrFail();

        expect($appointment->getAppointmentStatus())->toBe(AppointmentStatus::COMPLETED)
            ->and($appointment->getAttendance())->toBe(AttendanceStatus::ATTENDED)
            ->and($appointment->metadata['session_note'])->toBe('Discussed relapse triggers.')
            ->and($activity->description)->toBe('Session completed: attended')
            ->and(json_encode($activity->properties))->not->toContain('relapse');
    });

    it('refuses a change the status does not allow', function () {
        app(UpdateAppointmentStatus::class)->complete($this->appointment, $this->counsellor, AttendanceStatus::NO_SHOW, null, null);

        expect(fn () => app(UpdateAppointmentStatus::class)->cancel($this->appointment->refresh(), $this->counsellor, null))
            ->toThrow(ValidationException::class, 'cannot be marked Cancelled');
    });

    it('reminds the counsellor and attendee the day before, skipping cancelled bookings', function () {
        $tomorrow = bookWith($this->counsellor, serviceUserIn($this->team), now()->addDay()->toDateString());
        $cancelled = bookWith(counsellorIn($this->team), null, now()->addDay()->toDateString());
        app(UpdateAppointmentStatus::class)->cancel($cancelled, $this->counsellor, null);
        Notification::fake();

        $this->artisan('appointments:send-reminders')->expectsOutputToContain('Sent reminders for 1 appointments.')->assertSuccessful();

        Notification::assertSentTo($this->counsellor, AppointmentReminderNotification::class, fn ($notification): bool => $notification->schedule->is($tomorrow));
        Notification::assertSentOnDemandTimes(AppointmentReminderNotification::class, 1);
        Notification::assertSentTimes(AppointmentReminderNotification::class, 2);
    });
});

describe('counsellor access', function () {
    beforeEach(function () {
        Notification::fake();
    });

    it('shows a counsellor only their own appointments', function () {
        $counsellor = counsellorIn($this->team);
        $own = bookWith($counsellor, serviceUserIn($this->team), now()->addWeek()->toDateString());
        $someoneElses = bookWith(counsellorIn($this->team), serviceUserIn($this->team, ['email' => 'b@example.org']), now()->addWeek()->toDateString());

        $this->actingAs($counsellor);
        Filament::setTenant($this->team);

        livewire(ListAppointments::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$someoneElses]);

        $this->get(AppointmentResource::getUrl('view', ['record' => $someoneElses]))->assertNotFound();
        $this->get(AppointmentResource::getUrl('create'))->assertForbidden();
    });

    it('lets the counsellor record their session from the appointment page', function () {
        $counsellor = counsellorIn($this->team);
        $appointment = bookDaysAgo($counsellor, serviceUserIn($this->team), 1);

        $this->actingAs($counsellor);
        Filament::setTenant($this->team);

        livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
            ->callAction('complete_appointment', ['attendance' => AttendanceStatus::ATTENDED->value, 'session_note' => 'Good progress.'])
            ->assertHasNoActionErrors();

        expect($appointment->refresh()->getAttendance())->toBe(AttendanceStatus::ATTENDED);
    });

    it('requires a session note when the session was attended', function () {
        $counsellor = counsellorIn($this->team);
        $appointment = bookDaysAgo($counsellor, serviceUserIn($this->team), 1);

        $this->actingAs($counsellor);
        Filament::setTenant($this->team);

        livewire(ViewAppointment::class, ['record' => $appointment->getKey()])
            ->callAction('complete_appointment', ['attendance' => AttendanceStatus::ATTENDED->value, 'session_note' => ''])
            ->assertHasActionErrors(['session_note' => 'required']);
    });

    it('shows session notes to the counsellor, managers and admins only', function () {
        $counsellor = counsellorIn($this->team);
        $appointment = bookDaysAgo($counsellor, serviceUserIn($this->team), 1);
        app(UpdateAppointmentStatus::class)->complete($appointment, $counsellor, AttendanceStatus::ATTENDED, 'Private note', null);
        $appointment->refresh();

        $manager = signInToDirectoryAs($this->team, 'manager');
        $liaison = signInToDirectoryAs($this->team, 'liaison');

        expect($appointment->sessionNotesVisibleTo($counsellor))->toBeTrue()
            ->and($appointment->sessionNotesVisibleTo($manager))->toBeTrue()
            ->and($appointment->sessionNotesVisibleTo(counsellorIn($this->team)))->toBeFalse()
            ->and($appointment->sessionNotesVisibleTo($liaison))->toBeFalse();

        $this->actingAs($manager);
        livewire(ViewAppointment::class, ['record' => $appointment->getKey()])->assertSee('Private note');
    });
});

describe('counsellor track', function () {
    it('counts sessions held, no-shows, upcoming and unrecorded appointments per counsellor', function () {
        Notification::fake();
        signInToDirectoryAs($this->team, 'manager');
        $counsellor = counsellorIn($this->team);
        $update = app(UpdateAppointmentStatus::class);

        $update->complete(bookDaysAgo($counsellor, null, 3), $counsellor, AttendanceStatus::ATTENDED, 'Note', null);
        $update->complete(bookDaysAgo($counsellor, null, 5), $counsellor, AttendanceStatus::NO_SHOW, null, null);
        bookDaysAgo($counsellor, null, 2);
        bookWith($counsellor, null, now()->addDays(2)->toDateString());

        livewire(CounsellorSessionsWidget::class)
            ->assertTableColumnStateSet('attended_count', 1, $counsellor)
            ->assertTableColumnStateSet('no_show_count', 1, $counsellor)
            ->assertTableColumnStateSet('needs_recording_count', 1, $counsellor)
            ->assertTableColumnStateSet('upcoming_count', 1, $counsellor);
    });
});

describe('availability', function () {
    it('offers slots only from availability a manager has approved', function (?bool $approved, int $slots) {
        $counsellor = counsellorIn($this->team);
        $date = now()->addWeek()->next('Tuesday')->toDateString();
        availabilityFor($counsellor, $date, $approved);

        $offered = collect($counsellor->getBookableSlots($date, 60))->where('is_available', true);

        expect($offered)->toHaveCount($slots);
    })->with([
        'approved' => [true, 3],
        'waiting for approval' => [false, 0],
        'never approved' => [null, 0],
    ]);

    it('approves availability that managers added before approval was recorded', function () {
        $counsellor = counsellorIn($this->team);
        $legacy = availabilityFor($counsellor, now()->addWeek()->toDateString(), approved: null);
        $pending = availabilityFor(counsellorIn($this->team), now()->addWeek()->toDateString(), approved: false);

        (require database_path('migrations/2026_10_18_155420_approve_manager_created_availability.php'))->up();

        expect($legacy->refresh()->metadata['is_approved'])->toBeTrue()
            ->and($pending->refresh()->metadata['is_approved'])->toBeFalse();
    });

    it('stops staff changing availability a manager has locked', function () {
        $counsellor = counsellorIn($this->team);
        $availability = availabilityFor($counsellor, now()->addWeek()->toDateString(), approved: true);
        $availability->forceFill(['metadata' => [...$availability->metadata, 'is_locked' => true]])->save();
        $manager = signInToDirectoryAs($this->team, 'manager');
        $manager->givePermissionTo(Permission::firstOrCreate(['name' => 'Update:Schedule', 'guard_name' => 'web']));
        $counsellor->givePermissionTo(['Update:Schedule', Permission::firstOrCreate(['name' => 'Delete:Schedule', 'guard_name' => 'web'])]);

        expect($counsellor->can('update', $availability))->toBeFalse()
            ->and($counsellor->can('delete', $availability))->toBeFalse()
            ->and($manager->can('update', $availability))->toBeTrue();
    });
});

describe('counsellors and their clients', function () {
    beforeEach(function () {
        Notification::fake();
    });

    it('lets a counsellor read only the service users they have appointments with', function () {
        $counsellor = counsellorIn($this->team);
        $client = serviceUserIn($this->team);
        $stranger = serviceUserIn($this->team, ['email' => 'other@example.org']);
        bookWith($counsellor, $client, now()->addWeek()->toDateString());

        $this->actingAs($counsellor);
        Filament::setTenant($this->team);

        livewire(ListServiceUsers::class)
            ->assertCanSeeTableRecords([ServiceUser::query()->findOrFail($client->id)])
            ->assertCanNotSeeTableRecords([ServiceUser::query()->findOrFail($stranger->id)]);

        $this->get(ServiceUserResource::getUrl('view', ['record' => $client->id]))->assertOk();
        $this->get(ServiceUserResource::getUrl('view', ['record' => $stranger->id]))->assertNotFound();
        $this->get(ServiceUserResource::getUrl('edit', ['record' => $client->id]))->assertForbidden();
    });
});
