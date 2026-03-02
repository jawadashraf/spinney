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
        Schema::table('people', function (Blueprint $table) {
            // Demographics
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('ethnicity')->nullable();
            $table->text('address')->nullable();
            $table->string('postcode')->nullable();
            $table->boolean('no_fixed_address')->default(false);

            // Contact
            $table->string('phone')->nullable();
            $table->string('availability')->nullable();

            // Emergency Contact (already partially in person_relationships, but form has Name/Number)
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();

            // Substance Use History
            $table->jsonb('addictions')->nullable(); // Smoking, Drugs, Gambling, etc.
            $table->jsonb('substances_used')->nullable(); // Heroin, Cocaine, etc.
            $table->string('frequency_of_use')->nullable();
            $table->string('amount_of_use')->nullable();
            $table->jsonb('route_of_use')->nullable(); // Smoke, Sniff, etc.
            $table->string('age_first_used')->nullable();
            $table->boolean('overdosed_last_month')->default(false);
            $table->string('injection_history')->nullable(); // Never, Previous, Current

            // GP & Health
            $table->boolean('registered_with_gp')->default(false);
            $table->string('gp_name')->nullable();
            $table->text('gp_address')->nullable();

            // Referral & Additional Info
            $table->string('referral_type')->nullable(); // Self, Agency, etc.
            $table->string('referral_source_specify')->nullable();
            $table->jsonb('previous_input')->nullable(); // GP, Drug Agency, etc.
            $table->jsonb('other_issues')->nullable(); // Criminal Justice, Housing, etc.
            $table->text('reason_for_referral')->nullable();

            // Next Steps & Outcomes
            $table->jsonb('referral_targets')->nullable(); // Spiritual, Turning Point, etc.
            $table->string('referral_agency_specify')->nullable();
            $table->jsonb('intervention_offered')->nullable(); // Quran, Group Therapy, etc.
            $table->string('treatment_outcome')->nullable();
            $table->text('internal_notes')->nullable();

            // Consent & GDPR
            $table->boolean('consent_data_storage')->default(false);
            $table->boolean('consent_referrals')->default(false);
            $table->boolean('consent_communications')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth', 'gender', 'ethnicity', 'address', 'postcode', 'no_fixed_address',
                'phone', 'availability', 'emergency_contact_name', 'emergency_contact_number',
                'addictions', 'substances_used', 'frequency_of_use', 'amount_of_use', 'route_of_use',
                'age_first_used', 'overdosed_last_month', 'injection_history',
                'registered_with_gp', 'gp_name', 'gp_address',
                'referral_type', 'referral_source_specify', 'previous_input', 'other_issues', 'reason_for_referral',
                'referral_targets', 'referral_agency_specify', 'intervention_offered', 'treatment_outcome', 'internal_notes',
                'consent_data_storage', 'consent_referrals', 'consent_communications',
            ]);
        });
    }
};
