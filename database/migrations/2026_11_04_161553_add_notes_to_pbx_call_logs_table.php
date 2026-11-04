<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbx_call_logs', function (Blueprint $table): void {
            $table->foreignId('note_id')->nullable()->after('call_attempt_id')->constrained('notes')->nullOnDelete();
            $table->timestamp('notes_prompted_at')->nullable()->after('result');
            $table->timestamp('notes_skipped_at')->nullable()->after('notes_prompted_at');
        });
    }

    public function down(): void
    {
        Schema::table('pbx_call_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('note_id');
            $table->dropColumn(['notes_prompted_at', 'notes_skipped_at']);
        });
    }
};
