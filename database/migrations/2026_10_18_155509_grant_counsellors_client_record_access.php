<?php

declare(strict_types=1);

use App\Support\AppointmentPermissions;
use Illuminate\Database\Migrations\Migration;

/**
 * Lets counsellors and assessors read their own clients' service user records (limited to their clients by
 * ServiceUserResource).
 */
return new class extends Migration
{
    public function up(): void
    {
        AppointmentPermissions::ensure();
    }

    public function down(): void
    {
        //
    }
};
