<?php

declare(strict_types=1);

use App\Support\ReferralOrganisationPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ReferralOrganisationPermissions::ensure();
    }

    public function down(): void
    {
        //
    }
};

