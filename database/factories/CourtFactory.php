<?php

namespace Database\Factories;

use App\Enums\CourtType;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    protected $model = Court::class;

    public function definition(): array
    {
        return [
            'branch_id' => \App\Models\Branch::factory(),
            'name' => 'Field '.fake()->randomDigitNotNull(),
            'type' => fake()->randomElement([CourtType::Indoor, CourtType::Outdoor]),
            'capacity' => fake()->numberBetween(8, 22),
            'price_per_hour' => fake()->randomElement(['35.00', '45.00', '55.00']),
            'image_url' => null,
        ];
    }
}
