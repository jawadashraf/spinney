<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_mailing_group', function (Blueprint $table): void {
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('mailing_group_id')->constrained('mailing_groups')->cascadeOnDelete();

            $table->primary(['company_id', 'mailing_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_mailing_group');
    }
};
