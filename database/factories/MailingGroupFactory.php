<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MailingGroup;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailingGroup>
 */
final class MailingGroupFactory extends Factory
{
    protected $model = MailingGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
