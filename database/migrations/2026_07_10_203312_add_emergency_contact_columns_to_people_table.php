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
        Schema::table('people', function (Blueprint $table): void {
            $table->foreignId('emergency_contact_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();
            $table->string('emergency_contact_relation_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropForeign(['emergency_contact_id']);
            $table->dropColumn(['emergency_contact_id', 'emergency_contact_relation_type']);
        });
    }
};
