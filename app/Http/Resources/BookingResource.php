<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'court_id' => $this->court_id,
            'slot_id' => $this->slot_id,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'amount' => (string) $this->amount,
            'advance_amount' => (string) $this->advance_amount,
            'remaining_amount' => (string) $this->remaining_amount,
            'court' => CourtResource::make($this->whenLoaded('court')),
            'slot' => SlotResource::make($this->whenLoaded('slot')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
