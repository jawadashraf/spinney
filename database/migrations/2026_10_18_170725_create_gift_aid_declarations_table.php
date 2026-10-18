<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_aid_declarations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('people_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('declared_on');
            $table->string('method', 20);
            $table->boolean('covers_past_donations')->default(true);
            $table->boolean('covers_future_donations')->default(true);
            $table->text('address');
            $table->string('postcode', 20)->nullable();
            $table->date('withdrawn_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['people_id', 'declared_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_aid_declarations');
    }
};
