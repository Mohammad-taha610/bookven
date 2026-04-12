<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    /** Head-office roles: see and manage all branches in the API. */
    public function hasUnrestrictedBranchAccess(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::SuperAdmin], true);
    }

    public function accessibleBranchesQuery(): Builder
    {
        if ($this->hasUnrestrictedBranchAccess()) {
            return Branch::query();
        }

        $ids = $this->branches()->pluck('branches.id');

        return Branch::query()->whereIn('id', $ids);
    }

    public function canAccessBranchId(int $branchId): bool
    {
        if ($this->hasUnrestrictedBranchAccess()) {
            return Branch::query()->whereKey($branchId)->exists();
        }

        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    public function canAccessBranch(Branch $branch): bool
    {
        return $this->canAccessBranchId((int) $branch->id);
    }

    public function canAccessCourt(Court $court): bool
    {
        return $this->canAccessBranchId((int) $court->branch_id);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function canManageVenues(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Manager, UserRole::SuperAdmin], true);
    }
}
