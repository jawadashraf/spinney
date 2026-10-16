<?php

declare(strict_types=1);

namespace App\Actions\Outreach;

use App\Enums\OutreachEmailStatus;
use App\Enums\OutreachRecipientStatus;
use App\Jobs\SendOutreachEmailToRecipient;
use App\Models\OutreachEmail;
use App\Models\User;
use App\Support\MailQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sends a draft outreach email: records each recipient, then queues one message per address.
 *
 * Refuses when the email is not a draft, has no recipients, or needs more emails than the
 * mailbox's daily limit has left for outreach.
 */
final readonly class SendOutreachEmail
{
    public function __construct(
        private ResolveOutreachRecipients $resolveRecipients,
        private MailQuota $quota,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(OutreachEmail $email, User $by): int
    {
        $recipients = DB::transaction(function () use ($email, $by) {
            /** @var OutreachEmail $email */
            $email = OutreachEmail::query()->lockForUpdate()->findOrFail($email->getKey());

            if (! $email->isDraft()) {
                throw ValidationException::withMessages(['status' => 'This email has already been sent.']);
            }

            $recipients = $this->resolveRecipients->handle($email);

            if ($recipients->isEmpty()) {
                throw ValidationException::withMessages(['recipients' => 'No one to send to: check the selected groups, types and companies.']);
            }

            $available = $this->quota->availableForOutreach();

            if ($recipients->count() > $available) {
                throw ValidationException::withMessages([
                    'recipients' => "This email needs {$recipients->count()} sends but only {$available} are available for outreach in the next 24 hours. Narrow the recipients or try later.",
                ]);
            }

            $rows = $recipients->map(fn (array $recipient) => $email->recipients()->create([
                'team_id' => $email->team_id,
                'company_id' => $recipient['company']->getKey(),
                'people_id' => $recipient['contact']?->getKey(),
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'status' => OutreachRecipientStatus::Queued,
            ]));

            $email->forceFill([
                'status' => OutreachEmailStatus::Sending,
                'sent_by_id' => $by->id,
                'sent_at' => now(),
            ])->save();

            return $rows;
        });

        foreach ($recipients as $recipient) {
            SendOutreachEmailToRecipient::dispatch($recipient)->afterCommit();
        }

        return $recipients->count();
    }
}
