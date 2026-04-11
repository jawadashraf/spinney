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
        Schema::create('service_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            // Substance Use History
            $table->jsonb('addictions')->nullable();
            $table->jsonb('substances_used')->nullable();
            $table->string('frequency_of_use')->nullable();
            $table->string('amount_of_use')->nullable();
            $table->jsonb('route_of_use')->nullable();
            $table->string('age_first_used')->nullable();
            $table->boolean('overdosed_last_month')->default(false);
            $table->string('injection_history')->nullable();

            // GP & Health
            $table->boolean('registered_with_gp')->default(false);
            $table->string('gp_name')->nullable();
            $table->text('gp_address')->nullable();

            // Referral & Plan details
            $table->string('referral_type')->nullable();
            $table->string('referral_source_specify')->nullable();
            $table->jsonb('previous_input')->nullable();
            $table->jsonb('other_issues')->nullable();
            $table->text('reason_for_referral')->nullable();
            $table->string('target_service_team')->nullable();
            $table->string('engagement_status')->nullable();

            // Next Steps & Outcomes
            $table->jsonb('referral_targets')->nullable();
            $table->string('referral_agency_specify')->nullable();
            $table->jsonb('intervention_offered')->nullable();
            $table->string('treatment_outcome')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_user_profiles');
    }
};
