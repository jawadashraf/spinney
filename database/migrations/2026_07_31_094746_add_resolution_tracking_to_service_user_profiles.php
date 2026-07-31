<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_user_profiles', function (Blueprint $table) {
            $table->timestamp('support_flagged_at')->nullable();
            $table->timestamp('support_resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_user_profiles', function (Blueprint $table) {
            $table->dropColumn(['support_flagged_at', 'support_resolved_at']);
        });
    }
};
