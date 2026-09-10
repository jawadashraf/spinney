<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('call_plan_id')->nullable()->constrained('call_plans')->nullOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('parent_call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->dateTime('due_at');
            $table->dateTime('original_due_at');
            $table->string('status', 20)->default('scheduled');
            $table->string('outcome', 30)->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('action_required')->nullable();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->foreignId('note_id')->nullable()->constrained('notes')->nullOnDelete();
            $table->boolean('safeguarding_raised')->default(false);
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status', 'due_at']);
            $table->index(['assigned_user_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
