<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallOutcome;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $call_id
 * @property int|null $user_id
 * @property Carbon $attempted_at
 * @property CallOutcome $outcome
 * @property string|null $notes
 * @property-read PbxCallLog|null $pbxCallLog
 */
#[Fillable([
    'team_id',
    'call_id',
    'user_id',
    'attempted_at',
    'outcome',
    'notes',
])]
final class CallAttempt extends Model
{
    use HasTeam;

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => CallOutcome::class,
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Call, $this>
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The phone system call this attempt was logged from, if any.
     *
     * @return HasOne<PbxCallLog, $this>
     */
    public function pbxCallLog(): HasOne
    {
        return $this->hasOne(PbxCallLog::class);
    }
}
