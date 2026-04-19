<?php

namespace Database\Factories;

use App\Models\CustomFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldOption>
 */
class CustomFieldOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            'custom_field_id' => \App\Models\CustomField::factory(),
            'name' => $this->faker->word,
            'sort_order' => 0,
        ];
    }
}
