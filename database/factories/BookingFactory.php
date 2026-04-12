<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $court = Court::factory()->create();
        $slot = Slot::factory()->create([
            'court_id' => $court->id,
            'day_of_week' => 3,
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
        ]);

        return [
            'user_id' => User::factory(),
            'court_id' => $court->id,
            'slot_id' => $slot->id,
            'date' => now()->next('Wednesday')->toDateString(),
            'status' => BookingStatus::Pending,
            'amount' => '45.00',
            'advance_amount' => '0.00',
            'remaining_amount' => '45.00',
        ];
    }
}
