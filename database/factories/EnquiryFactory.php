<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
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
            'phone' => $this->faker->phoneNumber(),
            'category' => EnquiryCategory::FAMILY_ADVICE,
            'reason_for_contact' => $this->faker->paragraph(),
            'risk_flags' => $this->faker->sentence(),
            'safeguarding_flags' => false,
            'advice_given' => $this->faker->paragraph(),
            'action_taken' => $this->faker->paragraph(),
            'user_id' => User::factory(),
            'occurred_at' => now(),
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'status' => EnquiryStatus::OPEN,
            'source' => EnquirySourceType::PHONE,
            'direction' => EnquiryDirection::INBOUND,
            'call_type' => EnquiryCallType::GENERAL,
            'department_id' => null,
            'due_date' => null,
            'parent_enquiry_id' => null,
            'outcome' => null,
        ];
    }
}
