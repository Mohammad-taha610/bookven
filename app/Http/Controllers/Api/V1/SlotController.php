<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SlotResource;
use App\Models\Court;
use App\Services\SlotAvailabilityService;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function __construct(
        protected SlotAvailabilityService $availability
    ) {}

    public function forCourt(Request $request, Court $court)
    {
        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court.', 403);
        }

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $request->query('date');
        $rows = $this->availability->slotsWithAvailability($court, $date);

        $slots = $rows->map(function (array $row) {
            $slot = $row['slot'];
            $slot->is_booked = ! $row['available'];

            return (new SlotResource($slot))->resolve();
        });

        return $this->jsonSuccess([
            'court_id' => $court->id,
            'date' => $date,
            'slots' => $slots->values(),
        ]);
    }

    public function availability(Request $request, Court $court)
    {
        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court.', 403);
        }

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $request->query('date');
        $rows = $this->availability->slotsWithAvailability($court, $date);
        $available = $rows->where('available', true)->count();

        return $this->jsonSuccess([
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'type' => $court->type->value,
            ],
            'date' => $date,
            'total_slots' => $rows->count(),
            'available_slots' => $available,
            'slots' => $rows->map(function (array $row) {
                $slot = $row['slot'];
                $slot->is_booked = ! $row['available'];

                return (new SlotResource($slot))->resolve();
            })->values(),
        ]);
    }

    public function quick(Request $request)
    {
        $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $court = Court::findOrFail($request->query('court_id'));

        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court.', 403);
        }

        return $this->forCourt($request, $court);
    }

    public function times()
    {
        $times = [];
        for ($h = 6; $h < 24; $h++) {
            foreach ([0, 30] as $m) {
                if ($h === 23 && $m === 30) {
                    break;
                }
                $times[] = sprintf('%02d:%02d', $h, $m);
            }
        }

        return $this->jsonSuccess(['times' => $times]);
    }
}
