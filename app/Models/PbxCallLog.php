<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PbxCallResult;
use App\Models\Concerns\HasTeam;
use Database\Factories\PbxCallLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A finished call reported by the Yeastar phone system through its call journal.
 *
 * @property int $id
 * @property int $team_id
 * @property string $linked_id
 * @property string $crm_user_id
 * @property int|null $user_id
 * @property int|null $people_id
 * @property int|null $call_id
 * @property int|null $call_attempt_id
 * @property string $direction
 * @property string $disposition
 * @property string|null $status
 * @property int $talk_duration_sec
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string|null $contact_number
 * @property string|null $recording_url
 * @property PbxCallResult $result
 * @property-read User|null $user
 * @property-read ServiceUser|null $serviceUser
 * @property-read Call|null $call
 * @property-read CallAttempt|null $callAttempt
 */
#[Fillable([
    'team_id',
    'linked_id',
    'crm_user_id',
    'user_id',
    'people_id',
    'call_id',
    'call_attempt_id',
    'direction',
    'disposition',
    'status',
    'talk_duration_sec',
    'started_at',
    'ended_at',
    'contact_number',
    'recording_url',
    'result',
])]
final class PbxCallLog extends Model
{
    /** @use HasFactory<PbxCallLogFactory> */
    use HasFactory;

    use HasTeam;

    public const string DIRECTION_INBOUND = 'inbound';

    public const string DIRECTION_OUTBOUND = 'outbound';

    public const string DISPOSITION_ANSWERED = 'ANSWERED';

    public const string DISPOSITION_VOICEMAIL = 'VOICEMAIL';

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'result' => PbxCallResult::class,
            'talk_duration_sec' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ServiceUser, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'people_id');
    }

    /**
     * @return BelongsTo<Call, $this>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * @return BelongsTo<CallAttempt, $this>
     */
    public function callAttempt(): BelongsTo
    {
        return $this->belongsTo(CallAttempt::class);
    }

    public function isAnswered(): bool
    {
        return $this->disposition === self::DISPOSITION_ANSWERED && $this->talk_duration_sec > 0;
    }

    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }

    /**
     * Seconds from the call starting to it ending, which for an unanswered call is the ring time.
     */
    public function durationInSeconds(): int
    {
        return $this->ended_at === null ? 0 : (int) $this->started_at->diffInSeconds($this->ended_at, absolute: true);
    }

    public function formattedTalkDuration(): string
    {
        return gmdate($this->talk_duration_sec >= 3600 ? 'G:i:s' : 'i:s', $this->talk_duration_sec);
    }
}
