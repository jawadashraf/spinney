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
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->json('attribute_changes')->nullable()->after('causer_id');
            $table->dropColumn('batch_uuid');
        });

        DB::table($tableName)->whereNotNull('properties')->eachById(function ($row): void {
            $properties = json_decode($row->properties, true);
            if (! is_array($properties)) {
                return;
            }
            $changes = array_intersect_key($properties, array_flip(['attributes', 'old']));
            $remaining = array_diff_key($properties, array_flip(['attributes', 'old']));

            DB::table(config('activitylog.table_name', 'activity_log'))->where('id', $row->id)->update([
                'attribute_changes' => empty($changes) ? null : json_encode($changes),
                'properties' => empty($remaining) ? null : json_encode($remaining),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('activitylog.table_name', 'activity_log');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->uuid('batch_uuid')->nullable()->after('properties');
        });

        DB::table($tableName)->whereNotNull('attribute_changes')->eachById(function ($row): void {
            $attributeChanges = json_decode($row->attribute_changes, true);
            $properties = $row->properties ? json_decode($row->properties, true) : [];
            if (is_array($attributeChanges) && is_array($properties)) {
                $properties = array_merge($properties, $attributeChanges);
            }

            DB::table(config('activitylog.table_name', 'activity_log'))->where('id', $row->id)->update([
                'properties' => empty($properties) ? null : json_encode($properties),
            ]);
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
        });
    }
};
