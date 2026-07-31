<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Parental\HasParent;

final class ServiceUser extends People
{
    use HasParent;

    protected $attributes = [
        'is_service_user' => true,
    ];

    /**
     * @return HasOne<ServiceUserProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(ServiceUserProfile::class, 'person_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Spatie\Activitylog\Models\Activity, $this>
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}
