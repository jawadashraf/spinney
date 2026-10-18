<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->morphs('donor');
            $table->foreignId('gift_aid_declaration_id')->nullable()->constrained('gift_aid_declarations')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('donated_at');
            $table->string('payment_method', 30);
            $table->string('purpose', 30);
            $table->string('gift_aid_status', 20)->default('not_eligible');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'donated_at']);
            $table->index(['team_id', 'gift_aid_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
