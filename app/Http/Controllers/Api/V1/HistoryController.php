<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Support\ClubBookings;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function forUser(Request $request, int $id)
    {
        if ((int) $request->user()->id !== $id && ! $request->user()->canManageVenues()) {
            return $this->jsonError('Forbidden.', 403);
        }

        $user = User::findOrFail($id);

        $bookingsQuery = $user->bookings()
            ->with(['court.branch', 'slot', 'payments', 'user'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ((int) $request->user()->id !== $id && $request->user()->canManageVenues() && ! $request->user()->hasUnrestrictedBranchAccess()) {
            $branchIds = $request->user()->branches()->pluck('branches.id');
            $bookingsQuery->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
        }

        $codes = (clone $bookingsQuery)
            ->limit(300)
            ->pluck('booking_code')
            ->filter()
            ->unique()
            ->take(100)
            ->values();

        $bookings = $codes->isEmpty()
            ? collect()
            : Booking::query()
                ->whereIn('booking_code', $codes->all())
                ->with(['court.branch', 'slot', 'payments', 'user'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

        $activity = ActivityLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'activity', 'reference_type', 'reference_id', 'created_at']);

        return $this->jsonSuccess([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'bookings' => ClubBookings::collection($bookings),
            'activity' => $activity,
        ]);
    }
}
