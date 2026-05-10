<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Slot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SlotWebController extends Controller
{
    public const DAY_NAMES = [
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
        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'windows' => ['required', 'array', 'min:1'],
            'windows.*.start' => ['required', 'date_format:H:i'],
            'windows.*.end' => ['required', 'date_format:H:i'],
            'clear_selected_days' => ['sometimes', 'boolean'],
        ]);

        $courtId = (int) $validated['court_id'];
        $days = array_values(array_unique(array_map('intval', $validated['days'])));
        sort($days);

        $windows = [];
        foreach ($validated['windows'] as $i => $w) {
            $start = $this->normalizeTime($w['start']);
            $end = $this->normalizeTime($w['end']);
            if (strtotime($end) <= strtotime($start)) {
                throw ValidationException::withMessages([
                    "windows.{$i}.end" => 'End time must be after start time for each row.',
                ]);
            }
            $windows[$start.'|'.$end] = ['start' => $start, 'end' => $end];
        }
        $windows = array_values($windows);

        $clearFirst = $request->boolean('clear_selected_days');

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($courtId, $days, $windows, $clearFirst, &$created, &$skipped) {
            if ($clearFirst) {
                Slot::query()
                    ->where('court_id', $courtId)
                    ->whereIn('day_of_week', $days)
                    ->delete();
            }

            foreach ($days as $day) {
                foreach ($windows as $w) {
                    $query = Slot::query()
                        ->where('court_id', $courtId)
                        ->where('day_of_week', $day)
                        ->where('start_time', $w['start'])
                        ->where('end_time', $w['end']);

                    if (! $clearFirst && $query->exists()) {
                        $skipped++;

                        continue;
                    }

                    Slot::create([
                        'court_id' => $courtId,
                        'day_of_week' => $day,
                        'start_time' => $w['start'],
                        'end_time' => $w['end'],
                    ]);
                    $created++;
                }
            }
        });

        $msg = "Created {$created} slot".($created === 1 ? '' : 's').'.';
        if ($skipped > 0) {
            $msg .= " Skipped {$skipped} duplicate".($skipped === 1 ? '' : 's').' (already exist).';
        }

        return redirect()->route('admin.slots.index')->with('status', $msg);
    }

    public function edit(Slot $slot)
    {
        $courts = Court::query()->with('branch')->orderBy('branch_id')->orderBy('name')->get();
        $dayNames = self::DAY_NAMES;

        return view('admin.slots.edit', compact('slot', 'courts', 'dayNames'));
    }

    public function update(Request $request, Slot $slot)
    {
        $data = $this->validatedSingleSlot($request);
        $slot->update($data);

        return redirect()->route('admin.slots.index')->with('status', 'Slot updated.');
    }

    public function destroy(Slot $slot)
    {
        $slot->delete();

        return redirect()->route('admin.slots.index')->with('status', 'Slot deleted.');
    }

    private function validatedSingleSlot(Request $request): array
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

        $data['start_time'] = $this->normalizeTime($data['start_time']);
        $data['end_time'] = $this->normalizeTime($data['end_time']);

        return $data;
    }

    private function normalizeTime(string $time): string
    {
        $time = strlen($time) === 5 ? $time.':00' : $time;

        return $time;
    }
}
