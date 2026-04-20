<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasTeam;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Observers\ThirdPartyCarePlanObserver;
use Database\Factories\ThirdPartyCarePlanFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


#[ObservedBy(ThirdPartyCarePlanObserver::class)]
final class ThirdPartyCarePlan extends Model implements HasCustomFieldsContract, HasMedia
{
    use HasCreator;
    use HasCustomFields;
    use HasFactory;
    use HasTeam;
    use InteractsWithMedia;
    use SoftDeletes;


    /** @use HasFactory<ThirdPartyCarePlanFactory> */
    protected $fillable = [
        'team_id',
        'creator_id',
        'people_id',
        'service_user_email',
        'service_user_phone',
        'provider_name',
        'provider_contact',
        'status',
        'referral_date',
        'start_date',
        'end_date',
        'notes',
        'internal_notes',
    ];


    protected function casts(): array
    {
        return [
            'provider_contact' => 'array',
            'status' => ThirdPartyCarePlanStatus::class,
            'referral_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** @var array<string, mixed>|null */
    public ?array $custom_fields = null;

    /**
     * @return BelongsTo<People, $this>
     */
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'care_plan_user', 'care_plan_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canBeUpdated(): bool
    {
        return $this->status !== ThirdPartyCarePlanStatus::COMPLETED;
    }

    public function registerMediaCollections(): void

    {
        $this->addMediaCollection('attachments');
    }
}

