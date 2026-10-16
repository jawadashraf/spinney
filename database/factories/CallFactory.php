<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Enums\PbxCallResult;
use App\Models\Call;
use App\Models\PbxCallLog;
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

    /**
     * Answered on the phone system, with the liaison's notes still to be added.
     */
    public function awaitingNotes(): self
    {
        return $this->state(fn (): array => [
            'outcome' => CallOutcome::Answered,
            'attempt_count' => 1,
            'last_attempt_at' => now()->subMinutes(10),
        ])->afterCreating(function (Call $call): void {
            $attempt = $call->attempts()->create([
                'team_id' => $call->team_id,
                'user_id' => $call->assigned_user_id,
                'attempted_at' => $call->last_attempt_at,
                'outcome' => CallOutcome::Answered,
            ]);

            PbxCallLog::factory()->create([
                'team_id' => $call->team_id,
                'user_id' => $call->assigned_user_id,
                'people_id' => $call->people_id,
                'call_id' => $call->id,
                'call_attempt_id' => $attempt->id,
                'started_at' => $call->last_attempt_at,
                'result' => PbxCallResult::AwaitingNotes,
            ]);
        });
    }

    public function assignedTo(User $user): self
    {
        return $this->state(['assigned_user_id' => $user->id]);
    }
}
