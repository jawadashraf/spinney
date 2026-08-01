<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\TaskType;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Concerns\InvalidatesRelatedAiSummaries;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\Pivots\Taskable;
use App\Models\Pivots\TaskUser;
use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property-read string $createdBy
 * @property TaskType|string $type
 * @property string $title
 * @property Carbon|null $due_date
 * @property array<int, int>|null $old_assignees
 * @property string|null $old_priority
 *
 * @method void saveCustomFieldValue(CustomField $field, mixed $value)
 */
#[ObservedBy(TaskObserver::class)]
#[Fillable([
    'user_id',
    'title',
    'description',
    'status',
    'priority',
    'creation_source',
    'type',
    'department_id',
    'due_date',
])]
final class Task extends Model implements HasCustomFieldsContract
{
    use HasCreator;
    use HasCustomFields;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use InvalidatesRelatedAiSummaries;
    use SoftDeletes;
    use SortableTrait;

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
     * @return BelongsToMany<User, $this, TaskUser>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(TaskUser::class);
    }

    /**
     * @return MorphToMany<Company, $this, Taskable>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'taskable')->using(Taskable::class);
    }

    /**
     * @return MorphToMany<Opportunity, $this, Taskable>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'taskable')->using(Taskable::class);
    }

    /**
     * @return MorphToMany<People, $this, Taskable>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'taskable')->using(Taskable::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @param  Collection<int, int>  $departmentIds
     * @return Builder<Task>
     */
    public function scopeForDepartments(Builder $query, Collection $departmentIds): Builder
    {
        return $query->where(function (Builder $q) use ($departmentIds): void {
            $q->whereIn('department_id', $departmentIds)
                ->orWhereDoesntHave('assignees')
                ->orWhereHas('assignees', fn (Builder $sub) => $sub->where('users.id', auth()->id()));
        });
    }

    public function isFollowUpCall(): bool
    {
        return $this->type === TaskType::FollowUpCall || $this->type === TaskType::FollowUpCall->value;
    }
}
