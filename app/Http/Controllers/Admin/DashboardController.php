<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Enums\UserRole;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'branches_count' => Branch::query()->count(),
            'courts_count' => Court::query()->count(),
            'users_count' => User::query()->where('role', UserRole::User->value)->count(),
            'bookings_pending' => Booking::query()->where('status', 'Pending')->count(),
        ]);
    }
}
