<?php

declare(strict_types=1);

use App\Support\CompanyPermissions;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::query()->firstOrCreate(
            ['key' => config('outreach.template_key')],
            [
                'name' => ['en' => 'Outreach email'],
                'category' => 'marketing',
                'subject' => ['en' => 'News from Spinney Hill'],
                'body' => ['en' => '<p>Dear {{ recipient.name }},</p><p></p><p>With best wishes,<br>Spinney Hill Support Centre</p>'],
                'is_active' => true,
            ],
        );

        CompanyPermissions::ensure();
    }

    public function down(): void
    {
        EmailTemplate::query()->where('key', config('outreach.template_key'))->forceDelete();
    }
};
