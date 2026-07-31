<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Enums\SupportStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\ServiceUserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

final class ServiceUserProfile extends Model
{
    /** @use HasFactory<ServiceUserProfileFactory> */
    use HasFactory;

    use HasTeam;
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['support_status', 'engagement_status', 'target_service_team']);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->subject_type = $this->person->getMorphClass();
        $activity->subject_id = $this->person_id;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'addictions' => 'array',
            'substances_used' => 'array',
            'route_of_use' => 'array',
            'previous_input' => 'array',
            'other_issues' => 'array',
            'referral_targets' => 'array',
            'intervention_offered' => 'array',
            'target_service_team' => ServiceTeam::class,
            'engagement_status' => EngagementStatus::class,
            'overdosed_last_month' => 'boolean',
            'registered_with_gp' => 'boolean',
            'support_status' => SupportStatus::class,
            'support_flagged_at' => 'datetime',
            'support_resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class, 'person_id');
    }
}
