<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Support\MailQuota;
use Illuminate\Mail\Events\MessageSent;

/**
 * Counts every email the application sends against the mailbox's daily limit.
 */
final readonly class RecordOutgoingMail
{
    public function __construct(private MailQuota $quota) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $recipients = count($message->getTo()) + count($message->getCc()) + count($message->getBcc());

        $this->quota->record(
            max(1, $recipients),
            $message->getHeaders()->has(MailQuota::OUTREACH_HEADER),
        );
    }
}
