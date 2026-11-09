<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_organisations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('area_of_support')->index();
            $table->text('what_they_help_with');
            $table->string('telephone')->nullable();
            $table->string('phone_normalized')->nullable()->index();
            $table->string('email_or_text')->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->text('address')->nullable();
            $table->text('how_to_refer')->nullable();
            $table->text('volunteer_notes')->nullable();
            $table->string('priority', 50)->default('Routine')->index();
            $table->string('last_checked_at', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_organisations');
    }
};
