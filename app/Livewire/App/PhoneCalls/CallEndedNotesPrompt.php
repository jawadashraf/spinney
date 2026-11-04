<?php

declare(strict_types=1);

namespace App\Livewire\App\PhoneCalls;

use App\Filament\Resources\Calls\Actions\RecordCallOutcomeAction;
use App\Filament\Resources\PhoneCalls\Actions\AddCallNotesAction;
use App\Livewire\BaseLivewireComponent;
use App\Models\Call;
use App\Models\PbxCallLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

/**
 * Sits on every panel page and, shortly after the phone system reports that the signed-in user's answered call
 * has ended, opens the notes form for it. Each call is offered once, in whichever tab notices it first; calls
 * that are not dealt with stay on the Phone calls list as needing notes.
 */
final class CallEndedNotesPrompt extends BaseLivewireComponent
{
    /** How often an open, visible tab checks for a finished call. */
    public const int POLL_SECONDS = 15;

    /** Calls that ended longer ago than this are left to the Phone calls list. */
    public const int RECENT_MINUTES = 60;

    #[Locked]
    public ?int $teamId = null;

    public static function shouldRender(): bool
    {
        $user = auth()->user();

        return $user instanceof User && Filament::getTenant() !== null && $user->can('ViewAny:Call');
    }

    public function mount(): void
    {
        $this->teamId = Filament::getTenant()?->getKey();
    }

    public function checkForEndedCall(): void
    {
        $user = auth()->user();

        if ($this->mountedActions !== [] || $this->teamId === null || ! $user instanceof User) {
            return;
        }

        $log = PbxCallLog::query()
            ->where('team_id', $this->teamId)
            ->where('user_id', $user->id)
            ->needingNotes()
            ->whereNull('notes_prompted_at')
            ->where('ended_at', '>=', now()->subMinutes(self::RECENT_MINUTES))
            ->latest('ended_at')
            ->first();

        // Claim the call so another open tab does not prompt for it as well.
        if ($log === null || PbxCallLog::query()->whereKey($log->id)->whereNull('notes_prompted_at')->update(['notes_prompted_at' => now()]) === 0) {
            return;
        }

        setPermissionsTeamId($this->teamId);

        if ($log->hasLiaisonCallAwaitingNotes()) {
            $this->mountAction('recordOutcome', ['call' => $log->call_id]);

            return;
        }

        $this->mountAction('addNotes', ['log' => $log->id]);
    }

    public function addNotesAction(): Action
    {
        return AddCallNotesAction::make()
            ->record(fn (array $arguments): ?PbxCallLog => $this->findPhoneCall($arguments['log'] ?? null));
    }

    public function recordOutcomeAction(): Action
    {
        return RecordCallOutcomeAction::make()
            ->record(fn (array $arguments): ?Call => $this->findLiaisonCall($arguments['call'] ?? null))
            ->modalCancelActionLabel('Later');
    }

    public function render(): View
    {
        return view('livewire.app.phone-calls.call-ended-notes-prompt');
    }

    private function findPhoneCall(mixed $id): ?PbxCallLog
    {
        if (! is_numeric($id)) {
            return null;
        }

        return PbxCallLog::query()
            ->with(['person', 'company', 'call'])
            ->where('team_id', $this->teamId)
            ->visibleTo($this->authUser())
            ->find((int) $id);
    }

    private function findLiaisonCall(mixed $id): ?Call
    {
        if (! is_numeric($id)) {
            return null;
        }

        setPermissionsTeamId($this->teamId);

        return Call::query()
            ->where('calls.team_id', $this->teamId)
            ->visibleTo($this->authUser())
            ->find((int) $id);
    }
}
