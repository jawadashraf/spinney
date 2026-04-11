<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Models\Concerns\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceUserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceUserProfileFactory> */
    use HasFactory;

    use HasTeam;

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
