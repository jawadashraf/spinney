<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Outreach\BuildOutreachMail;
use App\Enums\OutreachEmailStatus;
use App\Enums\OutreachRecipientStatus;
use App\Models\OutreachEmailRecipient;
use FinityLabs\FinMail\Enums\EmailStatus;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Sends one outreach email to one address and records the result. A failed send is not retried,
 * so an address is never emailed twice; staff can see failures on the email's recipient list.
 */
final class SendOutreachEmailToRecipient implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public OutreachEmailRecipient $recipient) {}

    public function handle(BuildOutreachMail $buildMail): void
    {
        $recipient = $this->recipient;

        if ($recipient->status !== OutreachRecipientStatus::Queued) {
            return;
        }

        $email = $recipient->outreachEmail;
        $company = $recipient->company;

        $mail = $buildMail->handle(
            $email,
            $company,
            $recipient->contact,
            (string) $recipient->name,
            URL::signedRoute('outreach.unsubscribe', ['recipient' => $recipient]),
        );

        $log = SentEmail::query()->create([
            'email_template_id' => EmailTemplate::findByKey($email->template_key ?? (string) config('outreach.template_key'))?->getKey(),
            'sender' => (string) config('mail.from.address'),
            'to' => [$recipient->email],
            'subject' => $email->subject,
            'status' => EmailStatus::Queued,
            'sent_by' => $email->sent_by_id,
            'sendable_type' => $company->getMorphClass(),
            'sendable_id' => $company->getKey(),
        ]);

        $recipient->update(['sent_email_id' => $log->getKey()]);

        try {
            Mail::to($recipient->email, $recipient->name)->sendNow($mail->withLogging($log));

            $recipient->update(['status' => OutreachRecipientStatus::Sent, 'sent_at' => now(), 'error' => null]);
        } catch (Throwable $exception) {
            $recipient->update(['status' => OutreachRecipientStatus::Failed, 'error' => $exception->getMessage()]);
            report($exception);
        } finally {
            $this->markEmailSentWhenFinished();
        }
    }

    private function markEmailSentWhenFinished(): void
    {
        $email = $this->recipient->outreachEmail;

        if (! $email->recipients()->where('status', OutreachRecipientStatus::Queued)->exists()) {
            $email->forceFill(['status' => OutreachEmailStatus::Sent])->save();
        }
    }
}
