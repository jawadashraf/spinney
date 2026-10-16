<?php

declare(strict_types=1);

use App\Support\AppointmentPermissions;
use Illuminate\Database\Migrations\Migration;

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
