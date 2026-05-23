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
        Schema::create('enquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('people_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('category');
            $table->string('phone')->nullable();
            $table->text('caller_note')->nullable();
            $table->text('reason_for_contact');
            $table->text('risk_flags')->nullable();
            $table->boolean('safeguarding_flags')->default(false);
            $table->text('advice_given')->nullable();
            $table->text('action_taken')->nullable();
            $table->enum('referral_type', ['internal', 'external'])->nullable();
            $table->string('referral_destination')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('occurred_at');
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('source')->nullable();
            $table->string('direction')->default('inbound');
            $table->string('call_type')->default('general');
            $table->string('caller_type')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamp('due_date')->nullable();
            $table->foreignId('parent_enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
            $table->string('outcome')->nullable();

            $table->string('status')->default('open');
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
