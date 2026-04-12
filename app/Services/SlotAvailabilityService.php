<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Court;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotAvailabilityService
{
    public function dayOfWeekForDate(string $date): int
    {
        return Carbon::parse($date)->dayOfWeek; // 0 = Sunday in Carbon
    }

    /** @return Collection<int, Slot> */
    public function slotsForCourtOnDate(Court $court, string $date): Collection
    {
        $dow = $this->dayOfWeekForDate($date);

        return $court->slots()
            ->where('day_of_week', $dow)
            ->orderBy('start_time')
            ->get();
    }

    public function isSlotAvailable(Slot $slot, string $date): bool
    {
        return ! $slot->bookings()
            ->where('date', $date)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->exists();
    }

    /** @return Collection<int, array{slot: Slot, available: bool}> */
    public function slotsWithAvailability(Court $court, string $date): Collection
    {
        return $this->slotsForCourtOnDate($court, $date)->map(function (Slot $slot) use ($date) {
            return [
                'slot' => $slot,
                'available' => $this->isSlotAvailable($slot, $date),
            ];
        });
    }

    public function assertSlotAvailableOrFail(Slot $slot, string $date): void
    {
        if (! $this->isSlotAvailable($slot, $date)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'slot_id' => ['This time slot is no longer available for the selected date.'],
            ]);
        }
    }

    public function assertSlotMatchesDate(Slot $slot, string $date): void
    {
        $dow = $this->dayOfWeekForDate($date);
        if ((int) $slot->day_of_week !== $dow) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'slot_id' => ['This slot does not apply to the selected day of week.'],
            ]);
        }
    }
}
