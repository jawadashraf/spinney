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
        Schema::table('third_party_care_plans', function (Blueprint $table) {
            $table->string('service_user_email')->nullable()->after('people_id');
            $table->string('service_user_phone')->nullable()->after('service_user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('third_party_care_plans', function (Blueprint $table) {
            $table->dropColumn(['service_user_email', 'service_user_phone']);
        });
    }
};
