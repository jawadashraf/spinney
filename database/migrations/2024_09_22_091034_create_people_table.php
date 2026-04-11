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
        Schema::create('people', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('type')->nullable(); // STI Discriminator
            $table->boolean('is_service_user')->default(false);
            $table->boolean('is_locked')->default(false);

            // Demographics & Basic Contact
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('ethnicity')->nullable();
            $table->text('address')->nullable();
            $table->string('postcode')->nullable();
            $table->boolean('no_fixed_address')->default(false);
            $table->string('phone')->nullable();
            $table->string('availability')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();

            // Consent & GDPR
            $table->boolean('consent_data_storage')->default(false);
            $table->boolean('consent_referrals')->default(false);
            $table->boolean('consent_communications')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
