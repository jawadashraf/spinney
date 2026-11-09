<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReferralPriority;
use App\Models\ReferralOrganisation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralOrganisation>
 */
final class ReferralOrganisationFactory extends Factory
{
    protected $model = ReferralOrganisation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->company(),
            'area_of_support' => fake()->randomElement([
                'Domestic abuse',
                'Homelessness',
                'Housing support',
                'Mental health — urgent',
                'Food banks',
                'Substance misuse',
            ]),
            'what_they_help_with' => fake()->paragraph(),
            'telephone' => '0116 '.fake()->numerify('### ####'),
            'email_or_text' => fake()->safeEmail(),
            'website_url' => fake()->url(),
            'address' => fake()->address(),
            'how_to_refer' => fake()->sentence(),
            'volunteer_notes' => fake()->sentence(),
            'priority' => fake()->randomElement(ReferralPriority::cases()),
            'last_checked_at' => '05 Nov 2026',
            'is_active' => true,
        ];
    }
}
