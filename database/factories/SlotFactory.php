<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    protected $model = Slot::class;

    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'day_of_week' => fake()->numberBetween(0, 6),
        ];
    }
}
