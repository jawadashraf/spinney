<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SupportStatus;
use App\Models\Note;
use App\Models\ServiceUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ServiceUserNeedsAttention
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ServiceUser $serviceUser,
        public Note $note,
        public SupportStatus $status,
    ) {}
}
