<?php

declare(strict_types=1);

use App\Support\DonationPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DonationPermissions::ensure();
    }

    public function down(): void
    {
        //
    }
};
