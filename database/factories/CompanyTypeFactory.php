<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompanyType;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyType>
 */
final class CompanyTypeFactory extends Factory
{
    protected $model = CompanyType::class;

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
