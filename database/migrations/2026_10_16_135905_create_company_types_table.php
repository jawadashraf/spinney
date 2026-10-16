<?php

declare(strict_types=1);

use App\Models\CompanyType;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Team::query()->each(fn (Team $team) => CompanyType::createDefaultsFor($team));
    }

    public function down(): void
    {
        Schema::dropIfExists('company_types');
    }
};
