<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<People>
 */
final class PeopleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'team_id' => Team::factory(),
        ];
    }

    public function serviceUser(): self
    {
        return $this->state([
            'type' => 'service_user',
            'is_service_user' => true,
        ]);
    }

    /**
     * A named contact at a company in the outreach directory.
     */
    public function professional(): self
    {
        return $this->state([
            'type' => 'professional',
            'email' => fake()->safeEmail(),
            'job_title' => fake()->jobTitle(),
            'consent_communications' => true,
        ]);
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
