<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbx_call_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('linked_id', 100);
            $table->string('crm_user_id', 50)->default('');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('people_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->foreignId('call_attempt_id')->nullable()->constrained('call_attempts')->nullOnDelete();
            $table->string('direction', 10);
            $table->string('disposition', 20);
            $table->string('status', 20)->nullable();
            $table->unsignedInteger('talk_duration_sec')->default(0);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('contact_number', 32)->nullable();
            $table->text('recording_url')->nullable();
            $table->string('result', 30);
            $table->timestamps();

            $table->unique(['team_id', 'linked_id', 'crm_user_id']);
            $table->index(['people_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbx_call_logs');
    }
};
