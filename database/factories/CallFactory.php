<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Models\Call;
use App\Models\People;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
final class CallFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueAt = now()->addDay()->setTime(10, 0);

        return [
            'team_id' => Team::factory(),
            'people_id' => fn (array $attributes): int => People::factory()->create([
                'team_id' => $attributes['team_id'],
                'type' => 'service_user',
                'is_service_user' => true,
            ])->id,
            'reason' => fake()->sentence(),
            'due_at' => $dueAt,
            'original_due_at' => $dueAt,
            'status' => CallStatus::Scheduled,
        ];
    }

    public function overdue(): self
    {
        return $this->state(fn (): array => [
            'due_at' => now()->subDays(2),
            'original_due_at' => now()->subDays(2),
        ]);
    }

    public function dueToday(): self
    {
        return $this->state(function (): array {
            $dueAt = now()->addMinutes(30)->isToday() ? now()->addMinutes(30) : now()->endOfDay()->subMinute();

            return ['due_at' => $dueAt, 'original_due_at' => $dueAt];
        });
    }

    public function completed(): self
    {
        return $this->state(fn (): array => [
            'status' => CallStatus::Completed,
            'outcome' => CallOutcome::Answered,
            'attempt_count' => 1,
            'completed_at' => now()->subDay(),
            'last_attempt_at' => now()->subDay(),
            'notes' => fake()->paragraph(),
        ]);
    }

    public function assignedTo(User $user): self
    {
        return $this->state(['assigned_user_id' => $user->id]);
    }
}
