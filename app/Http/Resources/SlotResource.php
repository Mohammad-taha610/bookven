<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $start = $this->start_time;
        $end = $this->end_time;

        return [
            'id' => $this->id,
            'court_id' => $this->court_id,
            'start_time' => is_string($start) ? substr($start, 0, 5) : $start,
            'end_time' => is_string($end) ? substr((string) $end, 0, 5) : $end,
            'day_of_week' => $this->day_of_week,
            'is_booked' => $this->when(isset($this->is_booked), (bool) $this->is_booked),
        ];
    }
}
