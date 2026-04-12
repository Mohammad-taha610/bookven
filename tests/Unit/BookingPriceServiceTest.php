<?php

namespace Tests\Unit;

use App\Models\Court;
use App\Models\Slot;
use App\Services\BookingPriceService;
use PHPUnit\Framework\TestCase;

class BookingPriceServiceTest extends TestCase
{
    public function test_total_matches_hourly_rate_times_duration(): void
    {
        $court = new Court(['price_per_hour' => '40.00']);
        $slot = new Slot([
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
        ]);

        $svc = new BookingPriceService;
        $this->assertSame('80.00', $svc->totalForSlot($court, $slot));
    }
}
