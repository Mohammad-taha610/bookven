<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CourtResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\Branch;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();
        $user->load('branches');

        $nextBooking = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->whereDate('date', '>=', now()->toDateString())
            ->with(['court.branch', 'slot'])
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        $today = now()->toDateString();
        $todayQuery = Booking::query()
            ->with(['court.branch', 'slot', 'user'])
            ->whereDate('date', $today)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->orderBy('id');

        if ($user->canManageVenues()) {
            if (! $user->hasUnrestrictedBranchAccess()) {
                $branchIds = $user->branches()->pluck('branches.id');
                $todayQuery->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
            }
        } else {
            $todayQuery->where('user_id', $user->id);
        }

        $todaysBookings = $todayQuery->limit(50)->get();

        if ($user->hasUnrestrictedBranchAccess()) {
            $branches = Branch::query()->orderBy('name')->limit(6)->get();
        } else {
            $branches = $user->accessibleBranchesQuery()->orderBy('name')->limit(6)->get();
        }

        return $this->jsonSuccess([
            'screen' => 'home',
            'user' => new UserResource($user),
            'next_booking' => $nextBooking ? new BookingResource($nextBooking) : null,
            'todays_booking_timeline' => BookingResource::collection($todaysBookings),
            'branches_preview' => BranchResource::collection($branches),
        ]);
    }

    public function bookingNew(Request $request)
    {
        $user = $request->user();
        $branchId = $request->query('branch_id');

        $branches = $user->accessibleBranchesQuery()->orderBy('name')->get();

        $courts = collect();
        if ($branchId) {
            $branch = Branch::with(['courts' => fn ($q) => $q->orderBy('name')])->find($branchId);
            if ($branch && $user->canAccessBranch($branch)) {
                $courts = $branch->courts;
            }
        }

        return $this->jsonSuccess([
            'screen' => 'booking_new',
            'branches' => BranchResource::collection($branches),
            'selected_branch_id' => $branchId ? (int) $branchId : null,
            'courts' => CourtResource::collection($courts),
        ]);
    }
}
