<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CompanyVerificationStatus;
use App\Jobs\FetchFaviconForCompany;
use App\Models\Company;
use App\Models\User;

final readonly class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     */
    public function creating(Company $company): void
    {
        if (auth()->check()) {
            /** @var User $user */
            $user = auth()->user();
            $company->creator_id = $user->getKey();
        }
    }

    /**
     * Record when staff confirmed the company's details, and clear it if the record is no longer verified.
     */
    public function saving(Company $company): void
    {
        if ($company->isDirty('verification_status')) {
            $company->verified_at = $company->verification_status === CompanyVerificationStatus::Verified ? now() : null;
        }
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(): void
    {
        // FetchFaviconForCompany::dispatch($company)->afterCommit();
    }

    /**
     * Handle the Company "saved" event.
     * Invalidate AI summary when company data changes.
     */
    public function saved(Company $company): void
    {
        $company->invalidateAiSummary();
    }
}
