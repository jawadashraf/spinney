<?php

declare(strict_types=1);

use App\Actions\Calls\AddPhoneCallNote;
use App\Enums\NoteVisibility;
use App\Enums\SupportStatus;
use App\Events\ServiceUserNeedsAttention;
use App\Filament\Resources\Calls\CallResource;
use App\Filament\Resources\PhoneCalls\Pages\ListPhoneCalls;
use App\Livewire\App\PhoneCalls\CallEndedNotesPrompt;
use App\Models\Call;
use App\Models\Company;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use App\Models\User;
use App\Support\CallPermissions;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::query()->where('name', 'Spinney Hill')->firstOrFail();
    setPermissionsTeamId($this->team->id);
    CallPermissions::ensure($this->team->id);

    $this->manager = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->manager->assignRole('manager');

    $this->liaison = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->liaison->assignRole('liaison');

    actingAs($this->liaison);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->team);
});

it('saves call notes on the person\'s record and links them to the call', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id, 'type' => 'relative']);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $relative->id]);

    livewire(ListPhoneCalls::class)
        ->callAction(TestAction::make('addNotes')->table($log), data: ['notes' => 'Daughter asked about the lunch club.'])
        ->assertHasNoFormErrors()
        ->assertNotified('Call notes saved');

    $note = $relative->notes()->sole();

    expect($log->fresh()->note_id)->toBe($note->id)
        ->and($note->body)->toContain('Daughter asked about the lunch club.')
        ->and($note->creator_id)->toBe($this->liaison->id)
        ->and($log->fresh()->needsNotes())->toBeFalse();
});

it('saves call notes on a company\'s record', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'company_id' => $company->id]);

    livewire(ListPhoneCalls::class)
        ->callAction(TestAction::make('addNotes')->table($log), data: ['notes' => 'Confirmed the referral.'])
        ->assertHasNoFormErrors();

    expect($log->fresh()->note_id)->toBe($company->notes()->sole()->id);
});

it('raises a support concern from a service user call note', function () {
    Event::fake([ServiceUserNeedsAttention::class]);
    $serviceUser = People::factory()->serviceUser()->create(['team_id' => $this->team->id]);
    ServiceUserProfile::factory()->create(['team_id' => $this->team->id, 'person_id' => $serviceUser->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $serviceUser->id]);

    livewire(ListPhoneCalls::class)
        ->callAction(TestAction::make('addNotes')->table($log), data: [
            'notes' => 'Sounded very low.',
            'raise_concern' => true,
            'support_status' => SupportStatus::UrgentAttention->value,
        ])
        ->assertHasNoFormErrors();

    expect(ServiceUserProfile::query()->where('person_id', $serviceUser->id)->value('support_status'))->toBe(SupportStatus::UrgentAttention);
    Event::assertDispatched(ServiceUserNeedsAttention::class);
});

it('keeps a liaison\'s call note standard even if they ask for it to be restricted', function () {
    $relative = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $relative->id]);

    livewire(ListPhoneCalls::class)
        ->callAction(TestAction::make('addNotes')->table($log), data: ['notes' => 'Private.', 'visibility' => NoteVisibility::Restricted->value]);

    expect($relative->notes()->sole()->visibility)->toBe(NoteVisibility::Standard);
});

it('sends notes for a liaison call awaiting notes to Record outcome on the call', function () {
    $call = Call::factory()->assignedTo($this->liaison)->awaitingNotes()->create(['team_id' => $this->team->id]);
    $log = $call->attempts()->sole()->pbxCallLog;

    livewire(ListPhoneCalls::class)
        ->assertActionHidden(TestAction::make('addNotes')->table($log))
        ->assertActionVisible(TestAction::make('addLiaisonCallNotes')->table($log))
        ->assertActionHasUrl(TestAction::make('addLiaisonCallNotes')->table($log), CallResource::getUrl('view', ['record' => $call->id, 'action' => 'recordOutcome']));
});

it('takes a call off the needs-notes list when no notes are needed', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $person->id]);

    livewire(ListPhoneCalls::class)
        ->filterTable('needs_notes')
        ->assertCanSeeTableRecords([$log])
        ->callAction(TestAction::make('skipNotes')->table($log))
        ->assertCanNotSeeTableRecords([$log]);

    expect($log->fresh()->notes_skipped_at)->not->toBeNull()
        ->and($person->notes()->count())->toBe(0);
});

it('does not ask for notes on unanswered or unmatched calls', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);

    expect(PbxCallLog::factory()->unanswered()->create(['team_id' => $this->team->id, 'people_id' => $person->id])->needsNotes())->toBeFalse()
        ->and(PbxCallLog::factory()->create(['team_id' => $this->team->id])->needsNotes())->toBeFalse();
});

it('does not let a liaison add notes to a colleague\'s call', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->manager->id, 'people_id' => $person->id]);

    expect($log->isVisibleTo($this->liaison))->toBeFalse()
        ->and($log->isVisibleTo($this->manager))->toBeTrue();
});

it('opens the notes form once when the liaison\'s answered call ends', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $person->id]);

    livewire(CallEndedNotesPrompt::class)
        ->call('checkForEndedCall')
        ->assertActionMounted('addNotes')
        ->setActionData(['notes' => 'All fine.'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($log->fresh())->notes_prompted_at->not->toBeNull()
        ->note_id->toBe($person->notes()->sole()->id);
});

it('does not prompt again for a call another tab already offered', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $person->id, 'notes_prompted_at' => now()]);

    livewire(CallEndedNotesPrompt::class)
        ->call('checkForEndedCall')
        ->assertSet('mountedActions', []);
});

it('does not prompt for a colleague\'s call or a call that ended long ago', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $colleaguesCall = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->manager->id, 'people_id' => $person->id]);
    $oldCall = PbxCallLog::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->liaison->id,
        'people_id' => $person->id,
        'started_at' => now()->subHours(3),
        'ended_at' => now()->subHours(3)->addMinutes(5),
    ]);

    livewire(CallEndedNotesPrompt::class)
        ->call('checkForEndedCall')
        ->assertSet('mountedActions', []);

    expect($colleaguesCall->fresh()->notes_prompted_at)->toBeNull()
        ->and($oldCall->fresh()->notes_prompted_at)->toBeNull();
});

it('opens Record outcome when the call ended was a liaison call awaiting notes', function () {
    $call = Call::factory()->assignedTo($this->liaison)->awaitingNotes()->create(['team_id' => $this->team->id]);

    livewire(CallEndedNotesPrompt::class)
        ->call('checkForEndedCall')
        ->assertActionMounted('recordOutcome');

    expect($call->attempts()->sole()->pbxCallLog->notes_prompted_at)->not->toBeNull();
});

it('puts the call-ended prompt on panel pages only for staff who handle calls', function () {
    Filament::getPanel('app')->boot();

    expect(FilamentView::renderHook(PanelsRenderHook::BODY_END)->toHtml())->toContain('wire:name="app.phone-calls.call-ended-notes-prompt"');

    actingAs(User::factory()->create(['current_team_id' => $this->team->id]));

    expect(FilamentView::renderHook(PanelsRenderHook::BODY_END)->toHtml())->not->toContain('call-ended-notes-prompt');
});

it('links a call note back to its phone call', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $person->id]);

    $note = app(AddPhoneCallNote::class)->handle($log, $this->liaison, ['notes' => 'Booked a visit.']);

    expect($note->pbxCallLog->is($log))->toBeTrue();
});

it('asks for notes again when a call\'s note is deleted', function () {
    $person = People::factory()->create(['team_id' => $this->team->id]);
    $log = PbxCallLog::factory()->create(['team_id' => $this->team->id, 'user_id' => $this->liaison->id, 'people_id' => $person->id]);
    $note = app(AddPhoneCallNote::class)->handle($log, $this->liaison, ['notes' => 'Wrong person.']);

    $note->delete();

    expect($log->fresh())->note_id->toBeNull()
        ->needsNotes()->toBeTrue();
});
