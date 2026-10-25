<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Shows the service user's phone number as page text. The Linkus browser extension only turns numbers
 * in page text into click-to-call links, not numbers inside form fields.
 */
trait ShowsPhoneForClickToCall
{
    public function getSubheading(): string|Htmlable|null
    {
        $phone = $this->getRecord()->getAttribute('phone');

        return filled($phone) ? 'Phone: '.$phone : null;
    }
}
