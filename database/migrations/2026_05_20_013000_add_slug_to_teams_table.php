<?php

declare(strict_types=1);

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('teams', 'slug')) {
            Schema::table('teams', function (Blueprint $table): void {
                $table->string('slug')->nullable();
            });

            // Populate existing teams with slugs
            foreach (Team::all() as $team) {
                $team->slug = Str::slug($team->name);
                $team->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teams', 'slug')) {
            Schema::table('teams', function (Blueprint $table): void {
                $table->dropColumn('slug');
            });
        }
    }
};
