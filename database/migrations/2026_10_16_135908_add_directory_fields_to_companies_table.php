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
            $table->foreignId('company_type_id')->nullable()->after('account_owner_id')->constrained('company_types')->nullOnDelete();
            $table->string('status', 20)->default('active')->after('name');
            $table->string('email')->nullable()->after('status');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->text('address')->nullable()->after('website');
            $table->string('postcode', 20)->nullable()->after('address');
            $table->timestamp('email_invalid_at')->nullable()->after('postcode');
            $table->timestamp('unsubscribed_at')->nullable()->after('email_invalid_at');

            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'status']);
            $table->dropConstrainedForeignId('company_type_id');
            $table->dropColumn(['status', 'email', 'phone', 'website', 'address', 'postcode', 'email_invalid_at', 'unsubscribed_at']);
        });
    }
};
