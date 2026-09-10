<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CallFrequency;
use App\Models\CallPlan;
use App\Models\People;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallPlan>
 */
final class CallPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'people_id' => fn (array $attributes): int => People::factory()->create([
                'team_id' => $attributes['team_id'],
                'type' => 'service_user',
                'is_service_user' => true,
            ])->id,
            'frequency' => CallFrequency::Weekly,
            'starts_on' => today(),
            'preferred_time' => '10:00',
            'max_attempts' => 3,
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function custom(int $intervalDays): self
    {
        return $this->state([
            'frequency' => CallFrequency::Custom,
            'interval_days' => $intervalDays,
        ]);
    }
}
