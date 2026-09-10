<?php

declare(strict_types=1);

use App\Support\CallPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CallPermissions::ensure();
    }

    public function down(): void
    {
        //
    }
};
