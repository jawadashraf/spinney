<?php

declare(strict_types=1);

use App\Support\CompanyPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CompanyPermissions::ensure();
    }

    public function down(): void
    {
        //
    }
};
