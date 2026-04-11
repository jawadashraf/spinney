<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Models\People;
use App\Models\ServiceUserProfile;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceUserProfile>
 */
final class ServiceUserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'person_id' => People::factory(),
            'target_service_team' => fake()->randomElement(ServiceTeam::cases()),
            'engagement_status' => fake()->randomElement(EngagementStatus::cases()),
            'internal_notes' => fake()->paragraph(),
        ];
    }
}
