<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NoteVisibility;
use App\Models\Note;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Note>
 */
final class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'body' => '<p>'.$this->faker->paragraph().'</p>',
            'team_id' => Team::factory(),
        ];
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

    /**
     * A note only managers and admins can read.
     */
    public function restricted(): self
    {
        return $this->state(['visibility' => NoteVisibility::Restricted]);
    }
}
