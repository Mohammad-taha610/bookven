<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CourtResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\Branch;
use App\Support\ClubBookings;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();
        $user->load('branches');

        $nextRow = Booking::query()
            ->active()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->with(['court.branch', 'slot', 'payments', 'user'])
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        $nextBooking = $nextRow ? ClubBookings::one($nextRow) : null;

        $today = now()->toDateString();
        $todayQuery = Booking::query()
            ->active()
            ->with(['court.branch', 'slot', 'user', 'payments'])
            ->whereDate('date', $today)
            ->orderBy('id');

        if ($user->canManageVenues()) {
            if (! $user->hasUnrestrictedBranchAccess()) {
                $branchIds = $user->branches()->pluck('branches.id');
                $todayQuery->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
            }
        } else {
            $todayQuery->where('user_id', $user->id);
        }

        $codes = (clone $todayQuery)
            ->limit(150)
            ->pluck('booking_code')
            ->filter()
            ->unique()
            ->take(50)
            ->values();

        $todaysBookings = $codes->isEmpty()
            ? collect()
            : Booking::query()
                ->active()
                ->whereIn('booking_code', $codes->all())
                ->with(['court.branch', 'slot', 'user', 'payments'])
                ->orderBy('id')
                ->get();

        if ($user->hasUnrestrictedBranchAccess()) {
            $branches = Branch::query()->orderBy('name')->limit(6)->get();
        } else {
            $branches = $user->accessibleBranchesQuery()->orderBy('name')->limit(6)->get();
        }

        return $this->jsonSuccess([
            'screen' => 'home',
            'user' => new UserResource($user),
            'next_booking' => $nextBooking,
            'todays_booking_timeline' => ClubBookings::collection($todaysBookings),
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
