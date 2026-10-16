<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A message handed to the mailer, recorded to count against the mailbox's daily sending limit.
 *
 * @property int $recipients
 * @property bool $is_outreach
 * @property Carbon $sent_at
 */
#[Fillable(['recipients', 'is_outreach', 'sent_at'])]
final class OutgoingMail extends Model
{
    public $timestamps = false;

    protected $table = 'outgoing_mail_log';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipients' => 'integer',
            'is_outreach' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }
}
