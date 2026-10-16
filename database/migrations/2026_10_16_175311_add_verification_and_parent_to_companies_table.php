<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->foreignId('parent_company_id')->nullable()->after('company_type_id')->constrained('companies')->nullOnDelete();
            $table->string('verification_status', 20)->default('unverified')->after('status');
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->text('verification_note')->nullable()->after('verified_at');
            $table->text('source')->nullable()->after('verification_note');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_company_id');
            $table->dropColumn(['verification_status', 'verified_at', 'verification_note', 'source']);
        });
    }
};
