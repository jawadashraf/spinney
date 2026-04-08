<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ThirdPartyCarePlan;

final readonly class ThirdPartyCarePlanObserver
{
    public function creating(ThirdPartyCarePlan $thirdPartyCarePlan): void
    {
        if (auth('web')->check()) {
            $thirdPartyCarePlan->creator_id = auth('web')->id();
            $thirdPartyCarePlan->team_id = auth('web')->user()->current_team_id;
        }
    }

    public function saved(ThirdPartyCarePlan $thirdPartyCarePlan): void
    {
        if (isset($thirdPartyCarePlan->custom_fields) && is_array($thirdPartyCarePlan->custom_fields)) {
            $thirdPartyCarePlan->saveCustomFields($thirdPartyCarePlan->custom_fields);
        }
    }
}
