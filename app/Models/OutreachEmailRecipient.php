<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutreachRecipientStatus;
use App\Models\Concerns\HasTeam;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One address an outreach email was sent to: a company's general email or one of its contacts.
 *
 * @property int $team_id
 * @property int $outreach_email_id
 * @property int $company_id
 * @property int|null $people_id
 * @property int|null $sent_email_id
 * @property string $email
 * @property string|null $name
 * @property OutreachRecipientStatus $status
 * @property Carbon|null $sent_at
 * @property string|null $error
 */
#[Fillable(['team_id', 'company_id', 'people_id', 'sent_email_id', 'email', 'name', 'status', 'sent_at', 'error'])]
final class OutreachEmailRecipient extends Model
{
    use HasTeam;

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => OutreachRecipientStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OutreachEmail, $this>
     */
    public function outreachEmail(): BelongsTo
    {
        return $this->belongsTo(OutreachEmail::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The named contact, or null when sent to the company's general email.
     *
     * @return BelongsTo<People, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    /**
     * @return BelongsTo<SentEmail, $this>
     */
    public function sentEmail(): BelongsTo
    {
        return $this->belongsTo(SentEmail::class);
    }
}
