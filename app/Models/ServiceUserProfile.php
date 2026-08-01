<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Enums\SupportStatus;
use App\Models\Concerns\HasTeam;
use Database\Factories\ServiceUserProfileFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property SupportStatus|null $support_status
 */
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
            ->logOnly(['support_status', 'support_flagged_at', 'support_resolved_at', 'engagement_status', 'target_service_team']);
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $tenant = Filament::getTenant();
        $teamId = $tenant ? $tenant->getKey() : $this->team_id;
        $activity->setAttribute('team_id', $teamId);

        if ($eventName === 'updated' && $activity->attribute_changes) {
            $old = $activity->attribute_changes->get('old', []);
            $new = $activity->attribute_changes->get('attributes', []);

            if (isset($old['support_status']) && isset($new['support_status']) && $old['support_status'] !== $new['support_status']) {
                $oldStatus = str($old['support_status'])->headline();
                $newStatus = str($new['support_status'])->headline();
                $activity->description = "Changed support status from {$oldStatus} to {$newStatus}";
            }
        }
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
