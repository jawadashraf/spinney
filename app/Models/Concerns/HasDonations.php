<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A person or company that can give donations. Anyone with a donation is a donor, whatever else they are.
 */
trait HasDonations
{
    /**
     * @return MorphMany<Donation, $this>
     */
    public function donations(): MorphMany
    {
        return $this->morphMany(Donation::class, 'donor');
    }
}
