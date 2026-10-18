<?php

declare(strict_types=1);

use App\Enums\NoteVisibility;
use App\Filament\Actions\ViewNoteAction;
use App\Filament\Resources\ServiceUsers\Pages\EditServiceUser;
use App\Filament\Resources\ServiceUsers\RelationManagers\NotesRelationManager;
use App\Models\Note;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\Team;
use App\Models\User;
use App\Services\AI\RecordContextBuilder;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    foreach (['manager', 'counselor'] as $roleName) {
        $role = Role::query()->where('name', $roleName)->where('team_id', $this->team->id)->firstOrFail();

        foreach (['ViewAny:Note', 'View:Note', 'Create:Note', 'Update:Note', 'Delete:Note', 'ViewAny:ServiceUser', 'View:ServiceUser', 'Update:ServiceUser'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
    }

    $this->serviceUser = ServiceUser::query()->findOrFail(People::factory()->serviceUser()->create(['team_id' => $this->team->id])->id);
});

function noteFor(People $serviceUser, bool $restricted, string $body): Note
{
    $note = Note::factory()->state($restricted ? ['visibility' => NoteVisibility::Restricted] : [])->create([
        'team_id' => $serviceUser->team_id,
        'title' => $restricted ? 'Safeguarding concern' : 'Weekly check-in',
        'body' => $body,
    ]);

    $note->people()->attach($serviceUser, ['team_id' => $serviceUser->team_id]);

    return $note;
}

function staffMember(Team $team, string $role): User
{
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $user->teams()->syncWithoutDetaching([$team->id => ['role' => 'member']]);
    $user->assignRole($role);
    actingAs($user);
    Filament::setTenant($team);

    return $user;
}

describe('who can read restricted notes', function () {
    it('hides restricted notes from staff other than managers and admins', function () {
        $standard = noteFor($this->serviceUser, false, '<p>Doing well.</p>');
        $restricted = noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');

        $counsellor = staffMember($this->team, 'counselor');

        expect(Note::query()->pluck('id')->all())->toBe([$standard->id])
            ->and($counsellor->can('view', $restricted->withoutRelations()))->toBeFalse();

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->assertCanSeeTableRecords([$standard])
            ->assertCanNotSeeTableRecords([$restricted]);
    });

    it('shows restricted notes to managers and admins', function (string $role) {
        $restricted = noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');

        $user = staffMember($this->team, $role);

        expect(Note::query()->whereKey($restricted->id)->exists())->toBeTrue();

        if ($role === 'manager') {
            expect($user->can('view', $restricted))->toBeTrue();
        }
    })->with(['admin', 'manager']);

    it('hides restricted notes when nobody is signed in, such as in queued jobs', function () {
        noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');

        auth()->logout();

        expect(Note::query()->count())->toBe(0);
    });

    it('keeps a note standard when staff who cannot read restricted notes try to restrict it', function () {
        staffMember($this->team, 'counselor');

        $note = Note::factory()->create(['team_id' => $this->team->id, 'visibility' => NoteVisibility::Restricted]);

        expect(Note::withoutGlobalScopes()->findOrFail($note->id)->visibility)->toBe(NoteVisibility::Standard);
    });
});

describe('writing and reading', function () {
    it('lets a manager write a restricted note on a service user', function () {
        staffMember($this->team, 'manager');

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->callAction(TestAction::make('create')->table(), [
                'title' => 'Safeguarding concern',
                'support_status' => 'normal',
                'visibility' => NoteVisibility::Restricted->value,
                'body' => '<p>Disclosure details.</p>',
            ])
            ->assertHasNoActionErrors();

        expect(Note::query()->where('title', 'Safeguarding concern')->sole()->visibility)->toBe(NoteVisibility::Restricted);
    });

    it('does not offer the restricted option to other staff', function () {
        staffMember($this->team, 'counselor');

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->mountAction(TestAction::make('create')->table())
            ->assertActionMounted(TestAction::make('create')->table())
            ->assertFormFieldHidden('visibility');
    });

    it('lists a restricted note by title only, without its content', function () {
        noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');
        staffMember($this->team, 'manager');

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->assertSee('Safeguarding concern')
            ->assertDontSee('Disclosure details.');
    });

    it('records each opening of a restricted note in the audit log', function (string $action) {
        $restricted = noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');
        $manager = staffMember($this->team, 'manager');

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->mountAction(TestAction::make($action)->table($restricted));

        $activity = Activity::query()->where('subject_id', $restricted->id)->where('event', 'viewed')->sole();

        expect($activity->causer_id)->toBe($manager->id)
            ->and($activity->subject_type)->toBe($restricted->getMorphClass())
            ->and(json_encode($activity->properties))->not->toContain('Disclosure');
    })->with([
        'open' => ViewNoteAction::getDefaultName(),
        'edit' => EditAction::getDefaultName(),
    ]);

    it('does not log opening a standard note', function () {
        $standard = noteFor($this->serviceUser, false, '<p>Doing well.</p>');
        staffMember($this->team, 'manager');

        livewire(NotesRelationManager::class, ['ownerRecord' => $this->serviceUser, 'pageClass' => EditServiceUser::class])
            ->mountAction(TestAction::make(ViewNoteAction::getDefaultName())->table($standard));

        expect(Activity::query()->where('event', 'viewed')->exists())->toBeFalse();
    });
});

it('never puts restricted notes into AI summaries, even for a manager', function () {
    noteFor($this->serviceUser, false, '<p>Doing well.</p>');
    noteFor($this->serviceUser, true, '<p>Disclosure details.</p>');
    staffMember($this->team, 'manager');

    $context = app(RecordContextBuilder::class)->buildContext(People::query()->findOrFail($this->serviceUser->id));

    expect(json_encode($context))->toContain('Doing well')
        ->not->toContain('Disclosure details');
});
