<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        $this->backfillNames('users');
        $this->backfillNames('people');

        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('name')->nullable()->change();
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('name')->nullable()->change();
        });
    }

    private function backfillNames(string $table): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("UPDATE {$table} SET first_name = TRIM(SUBSTR(name, 1, INSTR(name, ' ') - 1)), last_name = TRIM(SUBSTR(name, INSTR(name, ' ') + 1)) WHERE name != ''");
            DB::statement("UPDATE {$table} SET last_name = first_name WHERE TRIM(last_name) = '' AND name != ''");
        } else {
            DB::statement("UPDATE {$table} SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = TRIM(SUBSTRING(name, LOCATE(' ', name) + 1)) WHERE name != ''");
            DB::statement("UPDATE {$table} SET last_name = first_name WHERE TRIM(last_name) = '' AND name != ''");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name']);
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
