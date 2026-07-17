<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Http\Resources\ClubbedBookingResource;
use App\Models\Booking;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ClubBookings
{
    /**
     * @param  iterable<Booking>  $bookings
     * @return Collection<int, Collection<int, Booking>>
     */
    public static function groups(iterable $bookings): Collection
    {
        return collect($bookings)
            ->groupBy(fn (Booking $b) => $b->booking_code ?: 'solo-'.$b->id)
            ->map(fn (Collection $group) => $group->values())
            ->values();
    }

    /**
     * @param  iterable<Booking>  $bookings
     */
    public static function collection(iterable $bookings): AnonymousResourceCollection
    {
        return ClubbedBookingResource::collection(self::groups($bookings));
    }

    public static function one(Booking $booking, array $with = ['court.branch', 'slot', 'payments', 'user']): ClubbedBookingResource
    {
        $siblings = self::siblingsQuery($booking)
            ->with($with)
            ->get();

        if ($siblings->isEmpty()) {
            $booking->loadMissing($with);
            $siblings = collect([$booking]);
        }

        return new ClubbedBookingResource($siblings);
    }

    /**
     * @return Collection<int, Booking>
     */
    public static function siblings(Booking $booking): Collection
    {
        $siblings = self::siblingsQuery($booking)->get();

        return $siblings->isEmpty() ? collect([$booking]) : $siblings;
    }

    public static function siblingsQuery(Booking $booking)
    {
        if ($booking->booking_code) {
            return Booking::query()
                ->where('booking_code', $booking->booking_code)
                ->orderBy('id');
        }

        return Booking::query()->whereKey($booking->id);
    }

    public static function resolveGroupStatus(Collection $rows): BookingStatus
    {
        $statuses = $rows->map(fn (Booking $b) => $b->status)->all();

        foreach ($statuses as $status) {
            if ($status === BookingStatus::Pending) {
                return BookingStatus::Pending;
            }
        }

        foreach ($statuses as $status) {
            if ($status === BookingStatus::Confirmed) {
                return BookingStatus::Confirmed;
            }
        }

        return BookingStatus::Cancelled;
    }
}
