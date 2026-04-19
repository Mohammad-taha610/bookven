<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\IndoorFacilityKind;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmBookingRequest;
use App\Http\Requests\Api\PayBookingRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Slot;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookings
    ) {}

    public function index(Request $request)
    {
        $query = Booking::query()
            ->with(['court.branch', 'slot'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (! $request->user()->canManageVenues() || ! $request->boolean('all')) {
            $query->where('user_id', $request->user()->id);
        } elseif (! $request->user()->hasUnrestrictedBranchAccess()) {
            $branchIds = $request->user()->branches()->pluck('branches.id');
            $query->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
        }

        if ($request->filled('date')) {
            $request->validate(['date' => ['date_format:Y-m-d']]);
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('branch_id')) {
            $request->validate(['branch_id' => ['integer']]);
            $branchId = (int) $request->query('branch_id');
            if (! $request->user()->canAccessBranchId($branchId)) {
                return $this->jsonError('You do not have access to this branch.', 403);
            }
            $query->whereHas('court', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($request->filled('indoor_facility_kind')) {
            $request->validate([
                'indoor_facility_kind' => [Rule::enum(IndoorFacilityKind::class)],
            ]);
            $kind = IndoorFacilityKind::from($request->query('indoor_facility_kind'));
            $query->whereHas('court', fn ($q) => $q->where('indoor_facility_kind', $kind));
        }

        $bookings = $query->limit(100)->get();

        return $this->jsonSuccess(BookingResource::collection($bookings));
    }

    public function today(Request $request)
    {
        $today = now()->toDateString();

        $query = Booking::query()
            ->with(['court.branch', 'slot', 'user'])
            ->whereDate('date', $today)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->orderBy('id');

        if (! $request->user()->canManageVenues()) {
            $query->where('user_id', $request->user()->id);
        } elseif (! $request->user()->hasUnrestrictedBranchAccess()) {
            $branchIds = $request->user()->branches()->pluck('branches.id');
            $query->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
        }

        return $this->jsonSuccess(BookingResource::collection($query->limit(100)->get()));
    }

    public function store(StoreBookingRequest $request)
    {
        $court = Court::findOrFail($request->court_id);
        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court or branch.', 403);
        }

        $slot = Slot::findOrFail($request->slot_id);

        $manualTotal = null;
        if ($request->filled('total_amount')) {
            if (! $request->user()->canManageVenues()) {
                return $this->jsonError('Only staff may set total_amount.', 403);
            }
            $manualTotal = (float) $request->total_amount;
        }

        $booking = $this->bookings->create(
            $request->user(),
            $court,
            $slot,
            $request->date,
            $request->has('advance_amount') ? (float) $request->advance_amount : null,
            $request->input('customer_name'),
            $request->input('customer_phone'),
            $manualTotal
        );

        return $this->jsonSuccess(new BookingResource($booking), 'Booking created.', 201);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);
        $booking->load(['court.branch', 'slot', 'payments']);

        return $this->jsonSuccess(new BookingResource($booking));
    }

    public function confirm(ConfirmBookingRequest $request, Booking $booking)
    {
        $this->authorize('confirm', $booking);

        $method = $request->payment_method
            ? PaymentMethod::from($request->payment_method)
            : null;

        $booking = $this->bookings->confirm($booking, $method);

        return $this->jsonSuccess(new BookingResource($booking), 'Booking confirmed.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookings->cancel($booking, $request->user());

        return $this->jsonSuccess(new BookingResource($booking), 'Booking cancelled.');
    }

    public function pay(PayBookingRequest $request, Booking $booking)
    {
        $this->authorize('pay', $booking);

        $amount = (float) $request->amount;
        if ($amount > (float) $booking->remaining_amount + 0.01) {
            return $this->jsonError('Amount exceeds remaining balance.', 422, [
                'amount' => ['Amount cannot be greater than the remaining balance.'],
            ]);
        }

        $payment = $this->bookings->recordPayment(
            $booking,
            PaymentMethod::from($request->payment_method),
            $amount
        );

        $booking->refresh()->load(['court.branch', 'slot', 'payments']);

        return $this->jsonSuccess([
            'booking' => new BookingResource($booking),
            'payment' => new PaymentResource($payment),
        ], 'Payment recorded.');
    }

    public function confirmationScreen(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);
        $booking->load(['court.branch', 'slot', 'payments', 'user']);

        return $this->jsonSuccess([
            'screen' => 'booking_confirmation',
            'booking' => new BookingResource($booking),
            'next_action' => $booking->remaining_amount > 0 ? 'pay_or_confirm' : 'confirm',
        ]);
    }

    public function confirmedScreen(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);
        $booking->load(['court.branch', 'slot', 'payments']);

        return $this->jsonSuccess([
            'screen' => 'booking_confirmed',
            'booking' => new BookingResource($booking),
        ]);
    }
}
