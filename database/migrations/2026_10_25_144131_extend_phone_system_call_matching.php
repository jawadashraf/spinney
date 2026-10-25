<?php

declare(strict_types=1);

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('phone_normalized', 32)->nullable()->after('phone');
            $table->index(['team_id', 'phone_normalized']);
        });

        Schema::table('pbx_call_logs', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('people_id')->constrained('companies')->nullOnDelete();
            $table->foreignId('enquiry_id')->nullable()->after('company_id')->constrained('enquiries')->nullOnDelete();
            $table->index(['company_id', 'started_at']);
            $table->index(['team_id', 'contact_number']);
        });

        DB::table('companies')
            ->whereNotNull('phone')
            ->select(['id', 'phone'])
            ->chunkById(500, function ($companies): void {
                foreach ($companies as $company) {
                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update(['phone_normalized' => PhoneNumber::normalize($company->phone)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('pbx_call_logs', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'contact_number']);
            $table->dropIndex(['company_id', 'started_at']);
            $table->dropConstrainedForeignId('enquiry_id');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'phone_normalized']);
            $table->dropColumn('phone_normalized');
        });
    }
};
