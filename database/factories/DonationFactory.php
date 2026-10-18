<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Company;
use App\Models\Donation;
use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
final class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_type' => 'people',
            'donor_id' => People::factory(),
            'amount' => fake()->randomFloat(2, 5, 500),
            'donated_at' => fake()->dateTimeBetween('-1 year'),
            'payment_method' => fake()->randomElement(DonationPaymentMethod::cases()),
            'purpose' => fake()->randomElement(DonationPurpose::cases()),
            'gift_aid_status' => GiftAidStatus::NotEligible,
        ];
    }

    public function fromCompany(): self
    {
        return $this->state(fn (): array => [
            'donor_type' => 'company',
            'donor_id' => Company::factory(),
        ]);
    }
}
