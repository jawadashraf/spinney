<?php

declare(strict_types=1);

use App\Models\Schedule;
use Illuminate\Database\Migrations\Migration;

/**
 * Availability with no approval flag could only have been added by a manager or admin (staff's own entries always
 * record one), so it is marked approved. Only the flag changes; nothing is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schedule::query()
            ->where('schedule_type', 'availability')
            ->each(function (Schedule $schedule): void {
                $metadata = $schedule->metadata ?? [];

                if (array_key_exists('is_approved', $metadata)) {
                    return;
                }

                $schedule->metadata = [...$metadata, 'is_approved' => true];
                $schedule->saveQuietly();
            });
    }

    public function down(): void
    {
        //
    }
};
