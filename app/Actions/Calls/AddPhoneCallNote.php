<?php

declare(strict_types=1);

namespace App\Actions\Calls;

use App\Enums\CreationSource;
use App\Enums\NoteVisibility;
use App\Enums\SupportStatus;
use App\Models\Company;
use App\Models\Note;
use App\Models\PbxCallLog;
use App\Models\People;
use App\Models\ServiceUser;
use App\Models\User;
use App\Support\ServiceUserSupportStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Writes the notes for a phone call as a note on the person's or company's record and links it to the call.
 * Calls with a service user's liaison call awaiting notes go through RecordCallOutcome instead.
 */
final readonly class AddPhoneCallNote
{
    /**
     * Keys: notes (required), raise_concern, support_status, visibility.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(PbxCallLog $log, User $by, array $data): Note
    {
        return DB::transaction(function () use ($log, $by, $data): Note {
            /** @var PbxCallLog $log */
            $log = PbxCallLog::query()->lockForUpdate()->findOrFail($log->id);

            if ($log->note_id !== null) {
                throw new InvalidArgumentException('This call already has notes.');
            }

            $contact = $log->person ?? $log->company ?? throw new InvalidArgumentException('Link the call to a person or company before adding notes.');

            $isServiceUser = $contact instanceof People && $contact->is_service_user;
            $supportStatus = $isServiceUser && (bool) ($data['raise_concern'] ?? false)
                ? self::enum(SupportStatus::class, $data['support_status'] ?? null) ?? SupportStatus::NeedsAttention
                : null;

            $note = $this->createNote($log, $contact, $by, $data, $supportStatus);

            $log->forceFill(['note_id' => $note->id])->save();

            if ($supportStatus !== null && ($serviceUser = ServiceUser::query()->find($contact->id)) !== null) {
                ServiceUserSupportStatus::flag($serviceUser, $note, $supportStatus);
            }

            return $note;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNote(PbxCallLog $log, People|Company $contact, User $by, array $data, ?SupportStatus $supportStatus): Note
    {
        $restricted = $by->canSeeRestrictedNotes()
            && self::enum(NoteVisibility::class, $data['visibility'] ?? null) === NoteVisibility::Restricted;

        $summary = ucfirst($log->direction).' call, '.($log->isAnswered() ? 'talk time '.$log->formattedTalkDuration() : mb_strtolower($log->disposition));

        /** @var Note $note */
        $note = $contact->notes()->create([
            'title' => 'Phone call – '.$log->started_at->format('d M Y, H:i'),
            'body' => '<p><strong>'.e($summary).'</strong></p><p>'.nl2br(e((string) ($data['notes'] ?? ''))).'</p>',
            'team_id' => $log->team_id,
            'creator_id' => $by->id,
            'creation_source' => CreationSource::WEB,
            'support_status' => $supportStatus,
            'visibility' => $restricted ? NoteVisibility::Restricted : NoteVisibility::Standard,
        ], ['team_id' => $log->team_id]);

        return $note;
    }

    /**
     * @template TEnum of SupportStatus|NoteVisibility
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    private static function enum(string $enum, mixed $value): SupportStatus|NoteVisibility|null
    {
        if ($value instanceof $enum) {
            return $value;
        }

        return is_string($value) ? $enum::tryFrom($value) : null;
    }
}
