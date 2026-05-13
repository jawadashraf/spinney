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
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('title');
            $table->longText('body');
            $table->string('creation_source', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('noteables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->index('team_id');

            $table->foreignId('note_id')->constrained()->cascadeOnDelete();

            $table->morphs('noteable');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noteables');
        Schema::dropIfExists('notes');
    }
};
