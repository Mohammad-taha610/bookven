<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Slot;

class BookingPriceService
{
    public function totalForSlot(Court $court, Slot $slot): string
    {
        $hours = $slot->durationHours();
        $total = (float) $court->price_per_hour * $hours;

        return number_format($total, 2, '.', '');
    }
}
