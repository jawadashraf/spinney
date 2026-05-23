<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Enquiry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SafeguardingFlagRaised
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Enquiry $enquiry) {}
}
