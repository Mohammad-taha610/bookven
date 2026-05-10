<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlotWebController extends Controller
{
    private const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function index()
    {
        $slots = Slot::query()
            ->with('court.branch')
            ->orderBy('court_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(30);

        return view('admin.slots.index', [
            'slots' => $slots,
            'dayNames' => self::DAY_NAMES,
        ]);
    }

    public function create()
    {
        $courts = Court::query()->with('branch')->orderBy('branch_id')->orderBy('name')->get();
        $dayNames = self::DAY_NAMES;

        return view('admin.slots.create', compact('courts', 'dayNames'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedSlot($request);
        Slot::create($data);

        return redirect()->route('admin.slots.index')->with('status', 'Slot created.');
    }

    public function edit(Slot $slot)
    {
        $courts = Court::query()->with('branch')->orderBy('branch_id')->orderBy('name')->get();
        $dayNames = self::DAY_NAMES;

        return view('admin.slots.edit', compact('slot', 'courts', 'dayNames'));
    }

    public function update(Request $request, Slot $slot)
    {
        $data = $this->validatedSlot($request);
        $slot->update($data);

        return redirect()->route('admin.slots.index')->with('status', 'Slot updated.');
    }

    public function destroy(Slot $slot)
    {
        $slot->delete();

        return redirect()->route('admin.slots.index')->with('status', 'Slot deleted.');
    }

    private function validatedSlot(Request $request): array
    {
        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        $start = strtotime($data['start_time']);
        $end = strtotime($data['end_time']);
        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $data['start_time'] = $data['start_time'].(strlen($data['start_time']) === 5 ? ':00' : '');
        $data['end_time'] = $data['end_time'].(strlen($data['end_time']) === 5 ? ':00' : '');

        return $data;
    }
}
