<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->string('visibility', 20)->default('standard')->after('body');
            $table->index(['team_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'visibility']);
            $table->dropColumn('visibility');
        });
    }
};
