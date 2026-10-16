<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->string('job_title')->nullable()->after('last_name');
            $table->timestamp('email_invalid_at')->nullable()->after('email');
            $table->timestamp('unsubscribed_at')->nullable()->after('email_invalid_at');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn(['job_title', 'email_invalid_at', 'unsubscribed_at']);
        });
    }
};
