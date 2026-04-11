<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Models\Concerns\HasAiSummary;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Observers\PeopleObserver;
use App\Services\AvatarService;
use Database\Factories\PeopleFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Parental\HasChildren;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 */
#[ObservedBy(PeopleObserver::class)]
class People extends Model implements HasCustomFieldsContract
{
    use HasAiSummary;
    use HasChildren;
    use HasCreator;
    use HasCustomFields;

    /** @use HasFactory<PeopleFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    protected $childTypes = [
        'service_user' => ServiceUser::class,
        'relative' => Relative::class,
        'donor' => Donor::class,
        'professional' => Professional::class,
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $custom_fields = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
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
            'is_service_user' => 'boolean',
            'is_locked' => 'boolean',
            'date_of_birth' => 'date',
            'no_fixed_address' => 'boolean',
            'consent_data_storage' => 'boolean',
            'consent_referrals' => 'boolean',
            'consent_communications' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<ServiceUserProfile, $this>
     */
    public function serviceUserProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ServiceUserProfile::class, 'person_id');
    }

    /**
     * @param  Builder<People>  $query
     * @return Builder<People>
     */
    #[Scope]
    protected function serviceUsers(Builder $query): Builder
    {
        return $query->where('is_service_user', true);
    }

    public function getAvatarAttribute(): string
    {
        return app(AvatarService::class)->generateAuto(name: $this->name, initialCount: 1);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedPeople(): BelongsToMany
    {
        return $this->belongsToMany(People::class, 'person_relationships', 'person_id', 'related_person_id')
            ->withPivot('relation_type', 'is_emergency_contact')
            ->withTimestamps();
    }

    public function relatedBy(): BelongsToMany
    {
        return $this->belongsToMany(People::class, 'person_relationships', 'related_person_id', 'person_id')
            ->withPivot('relation_type', 'is_emergency_contact')
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * @return HasMany<ThirdPartyCarePlan, $this>
     */
    public function thirdPartyCarePlans(): HasMany
    {
        return $this->hasMany(ThirdPartyCarePlan::class, 'people_id');
    }

    /**
     * @return HasMany<ThirdPartyCarePlan, $this>
     */
    public function activeCarePlans(): HasMany
    {
        return $this->hasMany(ThirdPartyCarePlan::class, 'people_id')
            ->whereIn('status', ['pending', 'in_progress']);
    }

    public function appointments()
    {
        // Appointments are accessed via Schedule metadata
        // This is a convenience method for querying
    }
}
