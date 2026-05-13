<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('title');

            $table->string('type', 50)->default('general_task')->index();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->dateTime('due_date')->nullable()->index();
            $table->flowforgePositionColumn('order_column');
            $table->string('creation_source', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('department_id');
        });

        Schema::create('taskables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->index('team_id');

            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            $table->morphs('taskable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskables');
        Schema::dropIfExists('tasks');
    }
};
