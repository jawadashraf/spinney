<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutreachEmailStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\OutreachEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An email written in the CRM and sent to companies in the outreach directory.
 *
 * @property int $team_id
 * @property int|null $creator_id
 * @property int|null $sent_by_id
 * @property string|null $template_key
 * @property string $subject
 * @property string $body
 * @property list<string>|null $attachments
 * @property array<string, string>|null $attachment_names
 * @property list<int|string>|null $mailing_group_ids
 * @property list<int|string>|null $company_type_ids
 * @property list<int|string>|null $company_ids
 * @property bool $send_to_general_emails
 * @property bool $send_to_contacts
 * @property OutreachEmailStatus $status
 * @property Carbon|null $sent_at
 */
#[Fillable([
    'team_id',
    'template_key',
    'subject',
    'body',
    'attachments',
    'attachment_names',
    'mailing_group_ids',
    'company_type_ids',
    'company_ids',
    'send_to_general_emails',
    'send_to_contacts',
])]
final class OutreachEmail extends Model
{
    /** @use HasFactory<OutreachEmailFactory> */
    use HasFactory;

    use HasTeam;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => OutreachEmailStatus::Draft,
        'send_to_general_emails' => true,
        'send_to_contacts' => true,
    ];

    protected static function booted(): void
    {
        self::creating(function (OutreachEmail $email): void {
            $user = auth('web')->user();

            if ($user instanceof User) {
                $email->creator_id ??= $user->id;
            }
        });
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => OutreachEmailStatus::class,
            'attachments' => 'array',
            'attachment_names' => 'array',
            'mailing_group_ids' => 'array',
            'company_type_ids' => 'array',
            'company_ids' => 'array',
            'send_to_general_emails' => 'boolean',
            'send_to_contacts' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === OutreachEmailStatus::Draft;
    }

    /**
     * @return HasMany<OutreachEmailRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(OutreachEmailRecipient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }
}
