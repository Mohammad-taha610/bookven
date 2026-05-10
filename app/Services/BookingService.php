<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        protected SlotAvailabilityService $slots,
        protected BookingPriceService $pricing
    ) {}

    public function create(
        User $user,
        Court $court,
        Slot $slot,
        string $date,
        ?float $advanceAmount = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?float $manualTotal = null
    ): Booking {
        $this->assertSlotBookableForCourt($court, $slot, $date);

        $total = $manualTotal !== null
            ? number_format(max(0, $manualTotal), 2, '.', '')
            : $this->pricing->totalForSlot($court, $slot);
        $advance = $advanceAmount !== null ? number_format(min((float) $advanceAmount, (float) $total), 2, '.', '') : '0.00';
        $remaining = number_format((float) $total - (float) $advance, 2, '.', '');

        return DB::transaction(function () use ($user, $court, $slot, $date, $total, $advance, $remaining, $customerName, $customerPhone) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'date' => $date,
                'status' => BookingStatus::Pending,
                'amount' => $total,
                'advance_amount' => $advance,
                'remaining_amount' => $remaining,
            ]);

            $this->logActivity($user, 'booking_created', $booking);

            return $booking->fresh(['court.branch', 'slot', 'user']);
        });
    }

    /**
     * Create one pending booking per slot. Amounts and advance are split by each slot’s
     * default price; optional staff manual total and advance apply to the combined booking.
     *
     * @param  list<Slot>  $slots
     * @return list<Booking>
     */
    public function createMany(
        User $user,
        Court $court,
        array $slots,
        string $date,
        ?float $advanceAmount = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?float $manualTotal = null
    ): array {
        if ($slots === []) {
            throw ValidationException::withMessages([
                'slot_ids' => ['At least one slot is required.'],
            ]);
        }

        $ordered = collect($slots)->unique('id')->sortBy([
            ['start_time', 'asc'],
            ['end_time', 'asc'],
            ['id', 'asc'],
        ])->values()->all();

        foreach ($ordered as $slot) {
            $this->assertSlotBookableForCourt($court, $slot, $date);
        }

        $baseBySlotId = [];
        foreach ($ordered as $slot) {
            $baseBySlotId[$slot->id] = (float) $this->pricing->totalForSlot($court, $slot);
        }

        $combined = array_sum($baseBySlotId);
        if ($manualTotal !== null) {
            $target = max(0.0, (float) $manualTotal);
            $amountsBySlotId = $this->distributeByWeights($baseBySlotId, $target);
        } else {
            $amountsBySlotId = array_map(fn (float $v) => round($v, 2), $baseBySlotId);
        }

        $combinedAmount = array_sum($amountsBySlotId);
        $advanceCap = $advanceAmount !== null ? min(max(0.0, (float) $advanceAmount), $combinedAmount) : 0.0;
        $advanceBySlotId = $advanceCap > 0.01
            ? $this->distributeByWeights($amountsBySlotId, $advanceCap)
            : array_fill_keys(array_keys($amountsBySlotId), 0.0);

        return DB::transaction(function () use ($user, $court, $ordered, $date, $amountsBySlotId, $advanceBySlotId, $customerName, $customerPhone) {
            $bookings = [];
            foreach ($ordered as $slot) {
                $id = $slot->id;
                $total = number_format($amountsBySlotId[$id], 2, '.', '');
                $advance = number_format($advanceBySlotId[$id], 2, '.', '');
                $remaining = number_format((float) $total - (float) $advance, 2, '.', '');

                $booking = Booking::create([
                    'user_id' => $user->id,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'court_id' => $court->id,
                    'slot_id' => $slot->id,
                    'date' => $date,
                    'status' => BookingStatus::Pending,
                    'amount' => $total,
                    'advance_amount' => $advance,
                    'remaining_amount' => $remaining,
                ]);

                $this->logActivity($user, 'booking_created', $booking);
                $bookings[] = $booking->fresh(['court.branch', 'slot', 'user']);
            }

            return $bookings;
        });
    }

    protected function assertSlotBookableForCourt(Court $court, Slot $slot, string $date): void
    {
        $this->slots->assertSlotMatchesDate($slot, $date);
        $this->slots->assertSlotAvailableOrFail($slot, $date);

        if ((int) $slot->court_id !== (int) $court->id) {
            throw ValidationException::withMessages([
                'court_id' => ['Court does not own this slot.'],
            ]);
        }
    }

    /**
     * Split $target across keys using non-negative weights (e.g. price per slot). Uses cent
     * rounding; last key absorbs remainder so the parts sum to $target within 0.01.
     *
     * @param  array<int, float>  $weights
     * @return array<int, float>
     */
    protected function distributeByWeights(array $weights, float $target): array
    {
        $keys = array_keys($weights);
        if ($keys === []) {
            return [];
        }

        $target = max(0.0, $target);
        $targetCents = (int) round($target * 100);
        if ($targetCents === 0) {
            return array_fill_keys($keys, 0.0);
        }

        $sumW = array_sum($weights);
        if ($sumW <= 0) {
            $n = count($keys);
            $base = intdiv($targetCents, $n);
            $rem = $targetCents % $n;
            $out = [];
            foreach ($keys as $i => $key) {
                $c = $base + ($i < $rem ? 1 : 0);
                $out[$key] = $c / 100;
            }

            return $out;
        }

        $allocatedCents = 0;
        $out = [];
        $lastIdx = count($keys) - 1;
        foreach ($keys as $i => $key) {
            if ($i === $lastIdx) {
                $out[$key] = ($targetCents - $allocatedCents) / 100;
            } else {
                $c = (int) floor($targetCents * ($weights[$key] / $sumW));
                $out[$key] = $c / 100;
                $allocatedCents += $c;
            }
        }

        return $out;
    }

    public function confirm(Booking $booking, ?PaymentMethod $method = null): Booking
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw ValidationException::withMessages([
                'booking' => ['Only pending bookings can be confirmed.'],
            ]);
        }

        return DB::transaction(function () use ($booking, $method) {
            $remaining = (float) $booking->remaining_amount;

            if ($remaining > 0.004) {
                if ($method === null) {
                    throw ValidationException::withMessages([
                        'payment_method' => ['Payment method is required to settle the remaining balance.'],
                    ]);
                }

                Payment::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $method,
                    'amount' => number_format($remaining, 2, '.', ''),
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                ]);

                $booking->update([
                    'advance_amount' => $booking->amount,
                    'remaining_amount' => '0.00',
                ]);
            }

            $booking->update(['status' => BookingStatus::Confirmed]);
            $this->logActivity($booking->user, 'booking_confirmed', $booking);

            return $booking->fresh(['court.branch', 'slot', 'payments']);
        });
    }

    public function cancel(Booking $booking, User $actor): Booking
    {
        if ($booking->status === BookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['Booking is already cancelled.'],
            ]);
        }

        $booking->update(['status' => BookingStatus::Cancelled]);
        $this->logActivity($actor, 'booking_cancelled', $booking);

        return $booking->fresh();
    }

    public function recordPayment(Booking $booking, PaymentMethod $method, float $amount): Payment
    {
        return DB::transaction(function () use ($booking, $method, $amount) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $method,
                'amount' => number_format($amount, 2, '.', ''),
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            $paidTotal = (float) $booking->fresh()->payments()
                ->where('status', PaymentStatus::Completed)
                ->sum('amount');
            $bookingTotal = (float) $booking->amount;
            $remaining = max(0, $bookingTotal - $paidTotal);

            $booking->update([
                'advance_amount' => number_format(min($paidTotal, $bookingTotal), 2, '.', ''),
                'remaining_amount' => number_format($remaining, 2, '.', ''),
            ]);

            if ($remaining <= 0.004 && $booking->status === BookingStatus::Pending) {
                $booking->update(['status' => BookingStatus::Confirmed]);
            }

            return $payment->fresh();
        });
    }

    protected function logActivity(?User $user, string $activity, Booking $booking): void
    {
        ActivityLog::create([
            'user_id' => $user?->id,
            'activity' => $activity,
            'reference_type' => Booking::class,
            'reference_id' => $booking->id,
        ]);
    }
}
