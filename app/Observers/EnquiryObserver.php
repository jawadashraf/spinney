<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\SafeguardingFlagRaised;
use App\Models\Enquiry;

final readonly class EnquiryObserver
{
    public function created(Enquiry $enquiry): void
    {
        if ($enquiry->safeguarding_flags) {
            SafeguardingFlagRaised::dispatch($enquiry);
        }
    }

    public function updated(Enquiry $enquiry): void
    {
        if ($enquiry->wasChanged('safeguarding_flags') && $enquiry->safeguarding_flags) {
            SafeguardingFlagRaised::dispatch($enquiry);
        }
    }
}
