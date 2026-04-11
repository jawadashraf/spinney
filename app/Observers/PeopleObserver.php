<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\People;

final readonly class PeopleObserver
{
    public function creating(People $people): void
    {
        if (auth('web')->check()) {
            $people->creator_id = auth('web')->id();
        }
    }

    /**
     * Handle the People "saved" event.
     * Invalidate AI summary when person data changes.
     */
    public function saved(People $people): void
    {
        $people->invalidateAiSummary();

        if (isset($people->custom_fields) && is_array($people->custom_fields)) {
            $people->saveCustomFields($people->custom_fields);
        }
    }

    public function deleted(People $people): void
    {
        if ($people->is_service_user) {
            $people->serviceUserProfile()->delete();
        }
    }

    public function restored(People $people): void
    {
        if ($people->is_service_user) {
            $people->serviceUserProfile()->withTrashed()->restore();
        }
    }

    public function forceDeleted(People $people): void
    {
        if ($people->is_service_user) {
            $people->serviceUserProfile()->withTrashed()->forceDelete();
        }
    }
}
