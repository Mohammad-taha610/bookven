<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CourtController;
use App\Http\Controllers\Api\V1\HistoryController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ScreenController;
use App\Http\Controllers\Api\V1\SlotController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

    Route::get('slots/times', [SlotController::class, 'times']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('branches', [BranchController::class, 'index']);
        Route::get('branches/{branch}/courts', [CourtController::class, 'indexForBranch']);
        Route::get('courts/{court}', [CourtController::class, 'show']);

        Route::get('courts/{court}/slots', [SlotController::class, 'forCourt']);
        Route::get('courts/{court}/availability', [SlotController::class, 'availability']);
        Route::get('slots/quick', [SlotController::class, 'quick']);

        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('screens/home', [ScreenController::class, 'home']);
        Route::get('screens/booking-new', [ScreenController::class, 'bookingNew']);

        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{booking}/pay', [BookingController::class, 'pay']);
        Route::get('bookings/{booking}/screen/confirmation', [BookingController::class, 'confirmationScreen']);
        Route::get('bookings/{booking}/screen/confirmed', [BookingController::class, 'confirmedScreen']);

        Route::get('payments/{payment}', [PaymentController::class, 'show']);

        Route::get('users/{id}/history', [HistoryController::class, 'forUser']);

        Route::middleware('role:admin|super_admin')->group(function () {
            Route::post('branches', [BranchController::class, 'store']);
            Route::put('branches/{branch}', [BranchController::class, 'update']);
            Route::delete('branches/{branch}', [BranchController::class, 'destroy']);
        });

        Route::middleware('role:admin|manager|super_admin')->group(function () {
            Route::post('courts', [CourtController::class, 'store']);
            Route::put('courts/{court}', [CourtController::class, 'update']);
            Route::delete('courts/{court}', [CourtController::class, 'destroy']);
        });
    });
});
