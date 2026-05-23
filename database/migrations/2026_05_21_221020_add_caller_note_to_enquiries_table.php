<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->text('caller_note')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('caller_note');
        });
    }
};
