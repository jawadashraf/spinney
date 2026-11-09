<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReferralPriority;
use App\Models\ReferralOrganisation;
use App\Models\Team;
use Illuminate\Database\Seeder;

final class ReferralOrganisationSeeder extends Seeder
{
    public function run(?int $teamId = null): void
    {
        $targetTeamId = $teamId ?? Team::query()->first()?->id ?? 1;

        $dataFile = __DIR__.'/referral_directory_data.json';
        if (! file_exists($dataFile)) {
            return;
        }

        $records = json_decode((string) file_get_contents($dataFile), true);
        if (! is_array($records)) {
            return;
        }

        foreach ($records as $row) {
            $priority = ReferralPriority::tryNormalize($row['priority'] ?? null);

            ReferralOrganisation::query()->updateOrCreate(
                [
                    'team_id' => $targetTeamId,
                    'name' => $row['name'],
                ],
                [
                    'area_of_support' => $row['area_of_support'],
                    'what_they_help_with' => $row['what_they_help_with'],
                    'telephone' => $row['telephone'],
                    'email_or_text' => $row['email_or_text'],
                    'website_url' => $row['website_url'],
                    'address' => $row['address'],
                    'how_to_refer' => $row['how_to_refer'],
                    'volunteer_notes' => $row['volunteer_notes'],
                    'priority' => $priority,
                    'last_checked_at' => $row['last_checked_at'] ?? '05 Nov 2026',
                    'is_active' => true,
                ]
            );
        }
    }
}

