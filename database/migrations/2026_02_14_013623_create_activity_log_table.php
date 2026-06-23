<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateActivityLogTable extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::connection(config('activitylog.database_connection'))->create($tableName, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('team_id')->nullable()->index();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::connection(config('activitylog.database_connection'))->dropIfExists($tableName);
    }
}
