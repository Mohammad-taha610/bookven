<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    protected function staffMayManage(User $user, Booking $booking): bool
    {
        if (! $user->canManageVenues()) {
            return false;
        }

        $booking->loadMissing('court');

        return $user->canAccessCourt($booking->court);
    }

    public function view(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id || $this->staffMayManage($user, $booking);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id || $this->staffMayManage($user, $booking);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id || $this->staffMayManage($user, $booking);
    }

    public function pay(User $user, Booking $booking): bool
    {
        return (int) $user->id === (int) $booking->user_id || $this->staffMayManage($user, $booking);
    }
}
