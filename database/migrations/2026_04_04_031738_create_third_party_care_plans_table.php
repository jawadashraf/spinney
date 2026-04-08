<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('third_party_care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained()->cascadeOnDelete();

            $table->string('provider_name');
            $table->json('provider_contact')->nullable();
            $table->string('status')->default('pending');
            $table->date('referral_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['people_id']);
            $table->index(['team_id']);
            $table->index(['creator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('third_party_care_plans');
    }
};
