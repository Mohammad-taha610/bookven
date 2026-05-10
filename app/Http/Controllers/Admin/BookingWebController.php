<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingWebController extends Controller
{
    public function index()
    {
        $bookings = Booking::query()
            ->with(['user', 'court.branch', 'slot'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function edit(Booking $booking)
    {
        $booking->load(['user', 'court.branch', 'slot']);
        $statuses = BookingStatus::cases();

        return view('admin.bookings.edit', compact('booking', 'statuses'));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0'],
            'advance_amount' => ['required', 'numeric', 'min:0'],
            'remaining_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $booking->update($data);

        return redirect()->route('admin.bookings.index')->with('status', 'Booking updated.');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('status', 'Booking deleted.');
    }
}
