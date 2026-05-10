<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BookingWebController;
use App\Http\Controllers\Admin\BranchTimingsWebController;
use App\Http\Controllers\Admin\BranchWebController;
use App\Http\Controllers\Admin\CourtWebController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IndoorTypeWebController;
use App\Http\Controllers\Admin\PaymentWebController;
use App\Http\Controllers\Admin\SlotWebController;
use App\Http\Controllers\Admin\UserWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login')->middleware('guest');
Route::post('/admin/login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:login']);

Route::middleware(['auth', 'super.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::resource('branches', BranchWebController::class)->except(['show']);
    Route::get('/timings', [BranchTimingsWebController::class, 'index'])->name('timings.index');
    Route::resource('indoor-types', IndoorTypeWebController::class)->except(['show']);
    Route::resource('courts', CourtWebController::class)->except(['show']);
    Route::resource('slots', SlotWebController::class)->except(['show']);
    Route::resource('bookings', BookingWebController::class)->except(['show', 'create', 'store']);
    Route::resource('payments', PaymentWebController::class)->except(['show', 'create', 'store']);
    Route::resource('users', UserWebController::class)->except(['show']);
});
