<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('source')->nullable()->after('converted_at');
            $table->string('direction')->default('inbound')->after('source');
            $table->string('call_type')->default('general')->after('direction');
            $table->string('caller_type')->nullable()->after('call_type');
            $table->foreignId('department_id')->nullable()->after('caller_type')->constrained('departments')->nullOnDelete();
            $table->timestamp('due_date')->nullable()->after('department_id');
            $table->foreignId('parent_enquiry_id')->nullable()->after('due_date')->constrained('enquiries')->nullOnDelete();
            $table->string('outcome')->nullable()->after('parent_enquiry_id');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['parent_enquiry_id']);
            $table->dropColumn([
                'source',
                'direction',
                'call_type',
                'caller_type',
                'department_id',
                'due_date',
                'parent_enquiry_id',
                'outcome',
            ]);
        });
    }
};
