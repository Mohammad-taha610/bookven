<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\ActivityLog;
use App\Models\User;
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
            ->with(['court.branch', 'slot'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ((int) $request->user()->id !== $id && $request->user()->canManageVenues() && ! $request->user()->hasUnrestrictedBranchAccess()) {
            $branchIds = $request->user()->branches()->pluck('branches.id');
            $bookingsQuery->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
        }

        $bookings = $bookingsQuery->limit(100)->get();

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
            'bookings' => BookingResource::collection($bookings),
            'activity' => $activity,
        ]);
    }
}
