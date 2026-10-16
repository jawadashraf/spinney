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
        Schema::table('people', function (Blueprint $table): void {
            $table->string('phone_normalized', 32)->nullable()->after('phone');
            $table->index(['team_id', 'phone_normalized']);
        });

        DB::table('people')
            ->whereNotNull('phone')
            ->select(['id', 'phone'])
            ->chunkById(500, function ($people): void {
                foreach ($people as $person) {
                    DB::table('people')
                        ->where('id', $person->id)
                        ->update(['phone_normalized' => PhoneNumber::normalize($person->phone)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'phone_normalized']);
            $table->dropColumn('phone_normalized');
        });
    }
};
