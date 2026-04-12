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

    public function create(User $user, Court $court, Slot $slot, string $date, ?float $advanceAmount = null): Booking
    {
        $this->slots->assertSlotMatchesDate($slot, $date);
        $this->slots->assertSlotAvailableOrFail($slot, $date);

        if ((int) $slot->court_id !== (int) $court->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'court_id' => ['Court does not own this slot.'],
            ]);
        }

        $total = $this->pricing->totalForSlot($court, $slot);
        $advance = $advanceAmount !== null ? number_format(min((float) $advanceAmount, (float) $total), 2, '.', '') : '0.00';
        $remaining = number_format((float) $total - (float) $advance, 2, '.', '');

        return DB::transaction(function () use ($user, $court, $slot, $date, $total, $advance, $remaining) {
            $booking = Booking::create([
                'user_id' => $user->id,
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
