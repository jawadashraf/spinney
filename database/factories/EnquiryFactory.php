<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enquiry>
 */
final class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'people_id' => null,
            'category' => \App\Enums\EnquiryCategory::FAMILY_ADVICE,
            'reason_for_contact' => $this->faker->paragraph(),
            'risk_flags' => $this->faker->sentence(),
            'safeguarding_flags' => false,
            'advice_given' => $this->faker->paragraph(),
            'action_taken' => $this->faker->paragraph(),
            'user_id' => \App\Models\User::factory(),
            'occurred_at' => now(),
            'team_id' => \App\Models\Team::factory(),
            'creator_id' => \App\Models\User::factory(),
        ];
    }
}
