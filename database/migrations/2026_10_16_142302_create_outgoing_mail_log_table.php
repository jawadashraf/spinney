<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per message the application hands to the mailer, used to count against the mailbox's daily limit.
     */
    public function up(): void
    {
        Schema::create('outgoing_mail_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('recipients');
            $table->boolean('is_outreach')->default(false);
            $table->timestamp('sent_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_log');
    }
};
