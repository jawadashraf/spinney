<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Models\People;
use App\Models\Team;
use App\Models\ThirdPartyCarePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThirdPartyCarePlan>
 */
final class ThirdPartyCarePlanFactory extends Factory
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
            'creator_id' => User::factory(),
            'people_id' => People::factory(),
            'provider_name' => $this->faker->randomElement(['Turning Point', 'Addiction Dependency Services', 'Other']),
            'provider_contact' => [
                'email' => $this->faker->companyEmail(),
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
            ],
            'status' => ThirdPartyCarePlanStatus::PENDING,
            'referral_date' => now(),
            'start_date' => null,
            'end_date' => null,
            'notes' => $this->faker->paragraph(),
            'internal_notes' => $this->faker->paragraph(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThirdPartyCarePlanStatus::IN_PROGRESS,
            'start_date' => now()->subDays(30),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThirdPartyCarePlanStatus::COMPLETED,
            'start_date' => now()->subDays(60),
            'end_date' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThirdPartyCarePlanStatus::CANCELLED,
        ]);
    }
}
