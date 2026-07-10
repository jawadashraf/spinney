<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomField>
 */
final class CustomFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug(),
            'type' => 'text',
            'entity_type' => Task::class,
            'active' => true,
            'system_defined' => false,
        ];
    }
}
