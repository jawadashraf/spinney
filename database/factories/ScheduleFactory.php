<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Models\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Zap\Enums\ScheduleTypes;

/**
 * @extends Factory<Schedule>
 */
final class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'schedulable_type' => User::class,
            'schedulable_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'schedule_type' => ScheduleTypes::APPOINTMENT->value,
            'start_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'end_date' => null,
            'is_recurring' => false,
            'frequency' => null,
            'frequency_config' => null,
            'metadata' => [
                'attendee_type' => AttendeeType::SERVICE_USER->value,
                'payment_type' => PaymentType::FREE->value,
                'counselor_type' => CounselorType::DRUG->value,
                'session_type' => SessionType::INDIVIDUAL->value,
                'appointment_status' => 'scheduled',
            ],
            'is_active' => true,
        ];
    }

    public function availability(): self
    {
        return $this->state(fn (array $attributes): array => [
            'schedule_type' => ScheduleTypes::AVAILABILITY->value,
            'name' => 'Office Hours',
            'metadata' => [
                'counselor_type' => CounselorType::DRUG->value,
                'slot_duration_minutes' => 60,
                'capacity' => 1,
                'is_locked' => false,
            ],
        ]);
    }

    public function recurring(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_recurring' => true,
            'frequency' => 'weekly',
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn (array $attributes): array => [
            'schedule_type' => ScheduleTypes::BLOCKED->value,
            'name' => 'Blocked Time',
            'metadata' => [
                'reason' => 'Out of office',
            ],
        ]);
    }
}
