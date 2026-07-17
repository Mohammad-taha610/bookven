<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmBookingRequest;
use App\Http\Requests\Api\PayBookingRequest;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Resources\ClubbedBookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Slot;
use App\Services\BookingService;
use App\Support\ClubBookings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookings
    ) {}

    public function index(Request $request)
    {
        $query = Booking::query()
            ->with(['court.branch', 'slot', 'payments', 'user'])
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
                'indoor_facility_kind' => ['string', 'max:32', Rule::exists('indoor_types', 'slug')],
            ]);
            $kind = (string) $request->query('indoor_facility_kind');
            $query->whereHas('court', fn ($q) => $q->where('indoor_facility_kind', $kind));
        }

        $bookings = $this->loadClubbedRows($query, 100);

        return $this->jsonSuccess(ClubBookings::collection($bookings));
    }

    public function today(Request $request)
    {
        $today = now()->toDateString();

        $query = Booking::query()
            ->with(['court.branch', 'slot', 'user', 'payments'])
            ->whereDate('date', $today)
            ->orderBy('id');

        if (! $request->user()->canManageVenues()) {
            $query->where('user_id', $request->user()->id);
        } elseif (! $request->user()->hasUnrestrictedBranchAccess()) {
            $branchIds = $request->user()->branches()->pluck('branches.id');
            $query->whereHas('court', fn ($q) => $q->whereIn('branch_id', $branchIds));
        }

        $bookings = $this->loadClubbedRows($query, 100);

        return $this->jsonSuccess(ClubBookings::collection($bookings));
    }

    public function store(StoreBookingRequest $request)
    {
        $court = Court::findOrFail($request->court_id);
        if (! $request->user()->canAccessCourt($court)) {
            return $this->jsonError('You do not have access to this court or branch.', 403);
        }

        $slotIds = $request->filled('slot_ids')
            ? array_values(array_unique($request->input('slot_ids')))
            : [(int) $request->slot_id];

        $slots = Slot::query()->whereIn('id', $slotIds)->get();
        if ($slots->count() !== count($slotIds)) {
            return $this->jsonError('One or more slots were not found.', 422);
        }

        foreach ($slots as $slot) {
            if ((int) $slot->court_id !== (int) $court->id) {
                return $this->jsonError('Each slot must belong to the selected court.', 422, [
                    'slot_ids' => ['Each slot must belong to the selected court.'],
                ]);
            }
        }

        $manualTotal = null;
        if ($request->filled('total_amount')) {
            if (! $request->user()->canManageVenues()) {
                return $this->jsonError('Only staff may set total_amount.', 403);
            }
            $manualTotal = (float) $request->total_amount;
        }

        $advance = $request->has('advance_amount') ? (float) $request->advance_amount : null;
        $customerName = $request->input('customer_name');
        $customerPhone = $request->input('customer_phone');

        $created = $this->bookings->createMany(
            $request->user(),
            $court,
            $slots->all(),
            $request->date,
            $advance,
            $customerName,
            $customerPhone,
            $manualTotal
        );

        return $this->jsonSuccess(
            new ClubbedBookingResource(collect($created)),
            'Booking created.',
            201
        );
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);

        return $this->jsonSuccess(ClubBookings::one($booking));
    }

    public function confirm(ConfirmBookingRequest $request, Booking $booking)
    {
        $this->authorize('confirm', $booking);

        $method = $request->payment_method
            ? PaymentMethod::from($request->payment_method)
            : null;

        try {
            $siblings = $this->bookings->confirmGroup($booking, $method);
        } catch (ValidationException $e) {
            throw $e;
        }

        return $this->jsonSuccess(
            new ClubbedBookingResource($siblings),
            'Booking confirmed.'
        );
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $this->bookings->cancelGroup($booking, $request->user());

        return $this->jsonSuccess(null, 'Booking cancelled.');
    }

    public function pay(PayBookingRequest $request, Booking $booking)
    {
        $this->authorize('pay', $booking);

        $amount = (float) $request->amount;

        try {
            [$siblings, $payment] = $this->bookings->recordPaymentForGroup(
                $booking,
                PaymentMethod::from($request->payment_method),
                $amount
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return $this->jsonSuccess([
            'booking' => new ClubbedBookingResource($siblings),
            'payment' => new PaymentResource($payment),
        ], 'Payment recorded.');
    }

    public function confirmationScreen(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);
        $siblings = ClubBookings::siblings($booking);
        $remaining = round($siblings->sum(fn (Booking $b) => (float) $b->remaining_amount), 2);

        return $this->jsonSuccess([
            'screen' => 'booking_confirmation',
            'booking' => ClubBookings::one($booking),
            'next_action' => $remaining > 0.004 ? 'pay_or_confirm' : 'confirm',
        ]);
    }

    public function confirmedScreen(Request $request, Booking $booking)
    {
        $this->authorize('view', $booking);

        return $this->jsonSuccess([
            'screen' => 'booking_confirmed',
            'booking' => ClubBookings::one($booking),
        ]);
    }

    /**
     * Load up to $limit clubbed groups without splitting sibling rows across the page boundary.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Booking>  $query
     * @return Collection<int, Booking>
     */
    protected function loadClubbedRows($query, int $limit): Collection
    {
        $codes = (clone $query)
            ->limit(max($limit * 3, 50))
            ->pluck('booking_code')
            ->filter()
            ->unique()
            ->take($limit)
            ->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        $with = $query->getEagerLoads();

        return Booking::query()
            ->whereIn('booking_code', $codes->all())
            ->with($with)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }
}
