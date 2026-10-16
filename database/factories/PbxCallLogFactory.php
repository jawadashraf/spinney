<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PbxCallResult;
use App\Models\PbxCallLog;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PbxCallLog>
 */
final class PbxCallLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subMinutes(10);

        return [
            'team_id' => Team::factory(),
            'linked_id' => fake()->unique()->numerify('##########.###'),
            'crm_user_id' => '',
            'direction' => PbxCallLog::DIRECTION_OUTBOUND,
            'disposition' => PbxCallLog::DISPOSITION_ANSWERED,
            'status' => 'Completed',
            'talk_duration_sec' => 240,
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addSeconds(255),
            'contact_number' => fake()->e164PhoneNumber(),
            'result' => PbxCallResult::LoggedOnly,
        ];
    }

    public function unanswered(string $disposition = 'NO ANSWER'): self
    {
        return $this->state(fn (array $attributes): array => [
            'disposition' => $disposition,
            'status' => 'Missed',
            'talk_duration_sec' => 0,
            'ended_at' => $attributes['started_at']->copy()->addSeconds(30),
        ]);
    }
}
