<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Court;
use App\Policies\BookingPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CourtPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Booking::class => BookingPolicy::class,
        Branch::class => BranchPolicy::class,
        Court::class => CourtPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
