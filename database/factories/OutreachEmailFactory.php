<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OutreachEmail;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutreachEmail>
 */
final class OutreachEmailFactory extends Factory
{
    protected $model = OutreachEmail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'subject' => fake()->sentence(4),
            'body' => '<p>Dear {{ recipient.name }},</p><p>'.fake()->paragraph().'</p>',
            'send_to_general_emails' => true,
            'send_to_contacts' => true,
        ];
    }
}
