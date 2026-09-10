<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CallFrequency;
use App\Enums\CallStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasTeam;
use App\Observers\CallPlanObserver;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\CallPlanFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $team_id
 * @property int $people_id
 * @property int|null $assigned_user_id
 * @property int|null $department_id
 * @property CallFrequency $frequency
 * @property int|null $interval_days
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $preferred_time
 * @property int $max_attempts
 * @property string|null $purpose
 * @property bool $is_active
 * @property-read ServiceUser|null $serviceUser
 * @property-read User|null $assignee
 * @property-read Call|null $nextCall
 */
#[ObservedBy(CallPlanObserver::class)]
#[Fillable([
    'team_id',
    'people_id',
    'assigned_user_id',
    'department_id',
    'frequency',
    'interval_days',
    'starts_on',
    'ends_on',
    'preferred_time',
    'max_attempts',
    'purpose',
    'is_active',
    'creator_id',
])]
final class CallPlan extends Model
{
    use HasCreator;

    /** @use HasFactory<CallPlanFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    public const string DEFAULT_CALL_TIME = '10:00';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'max_attempts' => 3,
        'is_active' => true,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['frequency', 'interval_days', 'assigned_user_id', 'is_active', 'ends_on']);
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
            'frequency' => CallFrequency::class,
            'interval_days' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'max_attempts' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (CallPlan $plan): void {
            $plan->team_id ??= Filament::getTenant()?->getKey() ?? auth('web')->user()?->current_team_id;

            $user = auth('web')->user();

            if ($plan->creator_id === null && $user instanceof User) {
                $plan->creator()->associate($user);
            }
        });
    }

    /**
     * @return BelongsTo<ServiceUser, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'people_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<Call, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * The earliest open call for this plan.
     *
     * @return HasOne<Call, $this>
     */
    public function nextCall(): HasOne
    {
        return $this->hasOne(Call::class)->ofMany(
            ['due_at' => 'min'],
            fn (Builder $query): Builder => $query->where('status', CallStatus::Scheduled),
        );
    }

    /**
     * @param  Builder<CallPlan>  $query
     * @return Builder<CallPlan>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if (Call::userSeesAllCalls($user)) {
            return $query;
        }

        if ($user->isRestrictedVolunteerLiaison()) {
            return $query->where('call_plans.assigned_user_id', $user->id);
        }

        $departmentIds = $user->departments()->pluck('departments.id');

        return $query->where(function (Builder $query) use ($user, $departmentIds): void {
            $query->where('call_plans.assigned_user_id', $user->id)
                ->orWhereIn('call_plans.department_id', $departmentIds);
        });
    }

    public function nextDueAfter(CarbonInterface $from): CarbonImmutable
    {
        return $this->frequency->nextDueFrom($from, $this->interval_days);
    }

    public function firstDueAt(): CarbonImmutable
    {
        $time = $this->preferred_time ? substr($this->preferred_time, 0, 5) : self::DEFAULT_CALL_TIME;

        return CarbonImmutable::parse($this->starts_on->toDateString().' '.$time);
    }

    public function coversDate(CarbonInterface $date): bool
    {
        return $this->is_active && ($this->ends_on === null || $date->lte($this->ends_on->copy()->endOfDay()));
    }
}
