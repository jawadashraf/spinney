<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_system_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_system_admin')->default(false)->after('password');
            });
        }

        // Copy data from system_administrators to users if the table exists
        if (Schema::hasTable('system_administrators')) {
            $admins = DB::table('system_administrators')->get();

            foreach ($admins as $admin) {
                DB::table('users')->updateOrInsert(
                    ['email' => $admin->email],
                    [
                        'name' => $admin->name,
                        'password' => $admin->password,
                        'email_verified_at' => $admin->email_verified_at,
                        'is_system_admin' => true,
                        'created_at' => $admin->created_at,
                        'updated_at' => $admin->updated_at,
                    ]
                );
            }

            // We drop the table in Down for safety during development?
            // No, the requirement is to unify, so we drop it here eventually.
            // For now, let's just keep it until we verify.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_system_admin');
        });
    }
};
