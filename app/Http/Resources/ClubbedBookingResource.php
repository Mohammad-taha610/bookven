<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Support\ClubBookings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ClubbedBookingResource extends JsonResource
{
    /**
     * @param  Collection<int, Booking>|Booking  $resource
     */
    public function __construct($resource)
    {
        if ($resource instanceof Booking) {
            $resource = collect([$resource]);
        }

        parent::__construct(collect($resource)->values());
    }

    public function toArray(Request $request): array
    {
        /** @var Collection<int, Booking> $rows */
        $rows = $this->resource
            ->sortBy(fn (Booking $b) => sprintf(
                '%s|%s|%020d',
                optional($b->slot)->start_time ?? '99:99:99',
                optional($b->slot)->end_time ?? '99:99:99',
                $b->id
            ))
            ->values();

        /** @var Booking $primary */
        $primary = $rows->sortBy('id')->first();
        $status = ClubBookings::resolveGroupStatus($rows);

        $amount = round($rows->sum(fn (Booking $b) => (float) $b->amount), 2);
        $advance = round($rows->sum(fn (Booking $b) => (float) $b->advance_amount), 2);
        $remaining = round($rows->sum(fn (Booking $b) => (float) $b->remaining_amount), 2);

        $slots = $rows->map(fn (Booking $b) => $b->slot)->filter()->values();
        $slotIds = $slots->pluck('id')->all();

        $payments = $rows
            ->flatMap(fn (Booking $b) => $b->relationLoaded('payments') ? $b->payments : collect())
            ->sortBy('id')
            ->values();

        return [
            'id' => $primary->id,
            'booking_code' => $primary->booking_code,
            'booking_ids' => $rows->pluck('id')->sort()->values()->all(),
            'user_id' => $primary->user_id,
            'customer_name' => $primary->customer_name,
            'customer_phone' => $primary->customer_phone,
            'court_id' => $primary->court_id,
            'slot_id' => $slotIds[0] ?? $primary->slot_id,
            'slot_ids' => $slotIds,
            'date' => $primary->date?->format('Y-m-d'),
            'status' => $status instanceof \BackedEnum ? $status->value : $status,
            'amount' => number_format($amount, 2, '.', ''),
            'advance_amount' => number_format($advance, 2, '.', ''),
            'remaining_amount' => number_format($remaining, 2, '.', ''),
            'court' => $primary->relationLoaded('court')
                ? CourtResource::make($primary->court)
                : null,
            'slot' => isset($slots[0]) ? SlotResource::make($slots[0]) : null,
            'slots' => SlotResource::collection($slots),
            'user' => $primary->relationLoaded('user') && $primary->user
                ? UserResource::make($primary->user)
                : null,
            'payments' => PaymentResource::collection($payments),
            'created_at' => $primary->created_at?->toIso8601String(),
        ];
    }
}
