<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use Database\Factories\CallFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $call_plan_id
 * @property int $people_id
 * @property int|null $assigned_user_id
 * @property int|null $department_id
 * @property int|null $parent_call_id
 * @property string|null $reason
 * @property Carbon $due_at
 * @property Carbon $original_due_at
 * @property CallStatus $status
 * @property CallOutcome|null $outcome
 * @property int $attempt_count
 * @property Carbon|null $last_attempt_at
 * @property Carbon|null $completed_at
 * @property int|null $completed_by_id
 * @property string|null $notes
 * @property string|null $action_required
 * @property Carbon|null $next_follow_up_at
 * @property int|null $note_id
 * @property bool $safeguarding_raised
 * @property-read ServiceUser|null $serviceUser
 * @property-read CallPlan|null $plan
 * @property-read User|null $assignee
 * @property-read User|null $completedBy
 * @property-read Call|null $parentCall
 * @property-read Call|null $followUpCall
 * @property-read Note|null $note
 * @property-read Team $team
 */
#[Fillable([
    'team_id',
    'call_plan_id',
    'people_id',
    'assigned_user_id',
    'department_id',
    'parent_call_id',
    'enquiry_id',
    'task_id',
    'reason',
    'due_at',
    'original_due_at',
    'status',
    'outcome',
    'attempt_count',
    'last_attempt_at',
    'completed_at',
    'completed_by_id',
    'notes',
    'action_required',
    'next_follow_up_at',
    'note_id',
    'safeguarding_raised',
    'creator_id',
])]
final class Call extends Model
{
    use HasCreator;

    /** @use HasFactory<CallFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    public const int DEFAULT_MAX_ATTEMPTS = 3;

    /**
     * Roles that may be assigned liaison calls.
     *
     * @var list<string>
     */
    public const array ASSIGNABLE_ROLES = ['liaison', 'volunteer_liaison', 'manager', 'admin'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
        'attempt_count' => 0,
        'safeguarding_raised' => false,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['assigned_user_id', 'due_at', 'status', 'outcome']);
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $tenant = Filament::getTenant();
        $activity->setAttribute('team_id', $tenant ? $tenant->getKey() : $this->team_id);
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => CallStatus::class,
            'outcome' => CallOutcome::class,
            'due_at' => 'datetime',
            'original_due_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'completed_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'attempt_count' => 'integer',
            'safeguarding_raised' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (Call $call): void {
            $call->team_id ??= Filament::getTenant()?->getKey() ?? auth('web')->user()?->current_team_id;
            $call->original_due_at ??= $call->due_at;

            $user = auth('web')->user();

            if ($call->creator_id === null && $user instanceof User) {
                $call->creator()->associate($user);
            }
        });
    }

    public static function userSeesAllCalls(User $user): bool
    {
        return $user->is_system_admin || $user->hasAnyRole(['super_admin', 'admin', 'manager']);
    }

    /**
     * @return BelongsTo<ServiceUser, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'people_id');
    }

    /**
     * @return BelongsTo<CallPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CallPlan::class, 'call_plan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Call, $this>
     */
    public function parentCall(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_call_id');
    }

    /**
     * @return HasOne<Call, $this>
     */
    public function followUpCall(): HasOne
    {
        return $this->hasOne(self::class, 'parent_call_id');
    }

    /**
     * @return HasMany<CallAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(CallAttempt::class)->latest('attempted_at');
    }

    /**
     * @return BelongsTo<Note, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * @return BelongsTo<Enquiry, $this>
     */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /**
     * Activity log entries for this call (used by the activity log relation manager).
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->activitiesAsSubject();
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('calls.status', CallStatus::Scheduled);
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function overdue(Builder $query): Builder
    {
        return $query->open()->where('calls.due_at', '<', now());
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function dueToday(Builder $query): Builder
    {
        return $query->open()->whereBetween('calls.due_at', [today(), today()->endOfDay()]);
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function upcoming(Builder $query): Builder
    {
        return $query->open()->where('calls.due_at', '>=', now());
    }

    /**
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function history(Builder $query): Builder
    {
        return $query->whereIn('calls.status', [CallStatus::Completed, CallStatus::Missed]);
    }

    /**
     * Restrict calls to those the given user may see.
     *
     * @param  Builder<Call>  $query
     * @return Builder<Call>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if (self::userSeesAllCalls($user)) {
            return $query;
        }

        if ($user->isRestrictedVolunteerLiaison()) {
            return $query->where('calls.assigned_user_id', $user->id);
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return $query->where(function (Builder $query) use ($user, $departmentIds): void {
            $query->where('calls.assigned_user_id', $user->id)
                ->orWhereIn('calls.department_id', $departmentIds)
                ->orWhere(fn (Builder $query): Builder => $query->whereNull('calls.assigned_user_id')->whereNull('calls.department_id'));
        });
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at->isPast();
    }

    public function maxAttempts(): int
    {
        return $this->plan->max_attempts ?? self::DEFAULT_MAX_ATTEMPTS;
    }

    public function hasAttemptsRemaining(): bool
    {
        return $this->attempt_count < $this->maxAttempts();
    }
}
