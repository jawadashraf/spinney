<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\TaskType;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\InvalidatesRelatedAiSummaries;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\Pivots\Taskable;
use App\Models\Pivots\TaskUser;
use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property int $id
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property string $createdBy
 *
 * @method void saveCustomFieldValue(CustomField $field, mixed $value)
 */
#[ObservedBy(TaskObserver::class)]
final class Task extends Model implements HasCustomFieldsContract
{
    use HasCreator;
    use HasCustomFields;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasTeam;
    use InvalidatesRelatedAiSummaries;
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'creation_source',
        'type',
        'department_id',
        'due_date',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'type' => TaskType::GeneralTask,
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'type' => TaskType::class,
            'due_date' => 'datetime',
        ];
    }

    /**
     * @var array{order_column_name: 'order_column', sort_when_creating: true}
     */
    public array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(TaskUser::class);
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taskable')->using(Taskable::class);
    }

    /**
     * @return MorphToMany<Opportunity, $this>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'taskable')->using(Taskable::class);
    }

    /**
     * @return MorphToMany<People, $this>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'taskable')->using(Taskable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return BelongsTo<Team, self> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Department, self> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @param Builder<Task> $query */
    public function scopeForDepartments(Builder $query, Collection $departmentIds): Builder
    {
        return $query->where(function (Builder $q) use ($departmentIds) {
            $q->whereIn('department_id', $departmentIds)
                ->orWhereDoesntHave('assignees')
                ->orWhereHas('assignees', fn (Builder $sub) => $sub->where('users.id', auth()->id()));
        });
    }

    public function isFollowUpCall(): bool
    {
        return $this->type === TaskType::FollowUpCall;
    }
}
