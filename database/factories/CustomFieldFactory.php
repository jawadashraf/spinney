<?php

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug,
            'type' => 'text',
            'entity_type' => \App\Models\Task::class,
            'active' => true,
            'system_defined' => false,
        ];
    }
}
