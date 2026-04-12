<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\CourtResource;
use App\Models\Branch;
use App\Models\Booking;
use Illuminate\Http\Request;

class ScreenController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();

        $nextBooking = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->whereDate('date', '>=', now()->toDateString())
            ->with(['court.branch', 'slot'])
            ->orderBy('date')
            ->orderBy('id')
            ->first();

        if ($user->hasUnrestrictedBranchAccess()) {
            $branches = Branch::query()->orderBy('name')->limit(6)->get();
        } else {
            $branches = $user->accessibleBranchesQuery()->orderBy('name')->limit(6)->get();
        }

        return $this->jsonSuccess([
            'screen' => 'home',
            'user' => [
                'name' => $user->name,
            ],
            'next_booking' => $nextBooking ? new BookingResource($nextBooking) : null,
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
