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
use App\Support\ClubBookings;
use Illuminate\Support\Collection;
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
        $created = $this->createMany(
            $user,
            $court,
            [$slot],
            $date,
            $advanceAmount,
            $customerName,
            $customerPhone,
            $manualTotal
        );

        return $created[0];
    }

    /**
     * Create one pending booking per slot, all sharing one booking_code.
     * Amounts and advance are split by each slot’s default price.
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
                $bookings[] = $booking;
            }

            $primaryId = (int) min(array_map(fn (Booking $b) => $b->id, $bookings));
            $slotIds = array_map(fn (Slot $s) => (int) $s->id, $ordered);
            $code = $this->makeBookingCode($date, $slotIds, $primaryId);

            foreach ($bookings as $booking) {
                $booking->update(['booking_code' => $code]);
            }

            return array_map(
                fn (Booking $b) => $b->fresh(['court.branch', 'slot', 'user', 'payments']),
                $bookings
            );
        });
    }

    /**
     * @param  list<int>  $slotIds
     */
    public function makeBookingCode(string $date, array $slotIds, int $primaryBookingId): string
    {
        sort($slotIds);
        $ymd = str_replace('-', '', $date);

        return 'BV'.$ymd.'-'.implode('-', $slotIds).'-'.$primaryBookingId;
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

    /**
     * @return Collection<int, Booking>
     */
    public function siblings(Booking $booking): Collection
    {
        return ClubBookings::siblings($booking);
    }

    public function confirmGroup(Booking $booking, ?PaymentMethod $method = null): Collection
    {
        $siblings = $this->siblings($booking);
        $status = ClubBookings::resolveGroupStatus($siblings);

        if ($status === BookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['Booking is cancelled.'],
            ]);
        }

        if ($status !== BookingStatus::Pending) {
            throw ValidationException::withMessages([
                'booking' => ['Only pending bookings can be confirmed.'],
            ]);
        }

        return DB::transaction(function () use ($siblings, $method) {
            $combinedRemaining = round($siblings->sum(fn (Booking $b) => (float) $b->remaining_amount), 2);

            if ($combinedRemaining > 0.004) {
                if ($method === null) {
                    throw ValidationException::withMessages([
                        'payment_method' => ['Payment method is required to settle the remaining balance.'],
                    ]);
                }

                /** @var Booking $primary */
                $primary = $siblings->sortBy('id')->first();
                Payment::create([
                    'booking_id' => $primary->id,
                    'payment_method' => $method,
                    'amount' => number_format($combinedRemaining, 2, '.', ''),
                    'status' => PaymentStatus::Completed,
                    'paid_at' => now(),
                ]);
            }

            foreach ($siblings as $row) {
                if ($row->status !== BookingStatus::Pending) {
                    continue;
                }

                $row->update([
                    'advance_amount' => $row->amount,
                    'remaining_amount' => '0.00',
                    'status' => BookingStatus::Confirmed,
                ]);
                $this->logActivity($row->user, 'booking_confirmed', $row);
            }

            return ClubBookings::siblingsQuery($siblings->first())
                ->with(['court.branch', 'slot', 'payments', 'user'])
                ->get();
        });
    }

    public function cancelGroup(Booking $booking, User $actor): Collection
    {
        $siblings = $this->siblings($booking);
        $status = ClubBookings::resolveGroupStatus($siblings);

        if ($status === BookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['Booking is already cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($siblings, $actor) {
            foreach ($siblings as $row) {
                if ($row->status === BookingStatus::Cancelled) {
                    continue;
                }
                $row->update(['status' => BookingStatus::Cancelled]);
                $this->logActivity($actor, 'booking_cancelled', $row);
            }

            return ClubBookings::siblingsQuery($siblings->first())->get();
        });
    }

    /**
     * @return array{0: Collection<int, Booking>, 1: Payment}
     */
    public function recordPaymentForGroup(Booking $booking, PaymentMethod $method, float $amount): array
    {
        $siblings = $this->siblings($booking);
        $status = ClubBookings::resolveGroupStatus($siblings);

        if ($status === BookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'booking' => ['Booking is cancelled.'],
            ]);
        }

        $combinedRemaining = round($siblings->sum(fn (Booking $b) => (float) $b->remaining_amount), 2);
        if ($amount > $combinedRemaining + 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Amount cannot be greater than the remaining balance.'],
            ]);
        }

        $weights = $siblings->mapWithKeys(
            fn (Booking $b) => [$b->id => (float) $b->remaining_amount]
        )->all();
        $shares = $this->distributeByWeights($weights, $amount);

        return DB::transaction(function () use ($siblings, $method, $amount, $shares) {
            /** @var Booking $primary */
            $primary = $siblings->sortBy('id')->first();

            $payment = Payment::create([
                'booking_id' => $primary->id,
                'payment_method' => $method,
                'amount' => number_format($amount, 2, '.', ''),
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            foreach ($siblings as $row) {
                $share = $shares[$row->id] ?? 0.0;
                $newAdvance = min((float) $row->amount, (float) $row->advance_amount + $share);
                $newRemaining = max(0.0, (float) $row->amount - $newAdvance);

                $updates = [
                    'advance_amount' => number_format($newAdvance, 2, '.', ''),
                    'remaining_amount' => number_format($newRemaining, 2, '.', ''),
                ];

                if ($newRemaining <= 0.004 && $row->status === BookingStatus::Pending) {
                    $updates['status'] = BookingStatus::Confirmed;
                }

                $row->update($updates);
            }

            $fresh = ClubBookings::siblingsQuery($primary)
                ->with(['court.branch', 'slot', 'payments', 'user'])
                ->get();

            return [$fresh, $payment->fresh()];
        });
    }

    public function confirm(Booking $booking, ?PaymentMethod $method = null): Booking
    {
        return $this->confirmGroup($booking, $method)->sortBy('id')->first();
    }

    public function cancel(Booking $booking, User $actor): Booking
    {
        return $this->cancelGroup($booking, $actor)->sortBy('id')->first();
    }

    public function recordPayment(Booking $booking, PaymentMethod $method, float $amount): Payment
    {
        [, $payment] = $this->recordPaymentForGroup($booking, $method, $amount);

        return $payment;
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
