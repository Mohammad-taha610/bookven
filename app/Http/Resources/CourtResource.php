<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'indoor_facility_kind' => $this->indoor_facility_kind instanceof \BackedEnum
                ? $this->indoor_facility_kind->value
                : $this->indoor_facility_kind,
            'capacity' => $this->capacity,
            'price_per_hour' => (string) $this->price_per_hour,
            'image_url' => $this->image_url,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
        ];
    }
}
