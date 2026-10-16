<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyEngagementType;
use App\Models\Company;
use App\Models\CompanyEngagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyEngagement>
 */
final class CompanyEngagementFactory extends Factory
{
    protected $model = CompanyEngagement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => fake()->randomElement(CompanyEngagementType::cases()),
            'occurred_on' => fake()->dateTimeBetween('-1 year'),
            'summary' => fake()->sentence(),
        ];
    }
}
