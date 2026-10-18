<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GiftAidDeclarationMethod;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftAidDeclaration>
 */
final class GiftAidDeclarationFactory extends Factory
{
    protected $model = GiftAidDeclaration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'people_id' => People::factory(),
            'declared_on' => today()->subMonth(),
            'method' => fake()->randomElement(GiftAidDeclarationMethod::cases()),
            'covers_past_donations' => true,
            'covers_future_donations' => true,
            'address' => fake()->streetAddress(),
            'postcode' => 'LE5 3QF',
        ];
    }
}
