<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->role instanceof UserRole ? $this->role : UserRole::tryFrom((string) $this->role);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $role?->value ?? $this->role,
            'role_label' => $role ? match ($role) {
                UserRole::User => 'Player',
                UserRole::Manager => 'Branch Manager',
                UserRole::Admin => 'Venue Admin',
                UserRole::SuperAdmin => 'Super Admin',
            } : null,
            'profile_image_url' => null,
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
