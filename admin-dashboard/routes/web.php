<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingDepartureController;
use App\Http\Controllers\Admin\BookingRescheduleController;
use App\Http\Controllers\Admin\BookingStatusController;
use App\Http\Controllers\Admin\BookingTreatmentController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeCareController;
use App\Http\Controllers\Admin\InsightController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TherapistController;
use App\Http\Controllers\Admin\TherapistLeaveController;
use App\Http\Controllers\Admin\TreatmentBranchController;
use App\Http\Controllers\Admin\TreatmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\BookingTrackingController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\TreatmentController as CustomerTreatmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('layanan/{slug}', CustomerTreatmentController::class)->name('treatments.show');
Route::view('kredit-foto', 'customer.photo-credits')->name('photo-credits');
Route::get('booking', [CustomerBookingController::class, 'create'])->name('bookings.create');
Route::post('booking', [CustomerBookingController::class, 'store'])->middleware('throttle:booking-store')->name('bookings.store');
Route::get('cek-booking', [BookingTrackingController::class, 'lookupForm'])->name('bookings.lookup');
Route::post('cek-booking', [BookingTrackingController::class, 'lookup'])->middleware('throttle:booking-lookup')->name('bookings.lookup.submit');
Route::middleware('throttle:booking-lookup')->group(function () {
    Route::get('booking/{code}', [BookingTrackingController::class, 'show'])->name('bookings.show');
    Route::get('booking/{code}/kuitansi', [BookingTrackingController::class, 'receipt'])->name('bookings.receipt');
    Route::post('booking/{code}/permintaan', [BookingTrackingController::class, 'requestChange'])->name('bookings.request-change');
});

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'active'])->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('treatments', TreatmentController::class)->except(['show', 'destroy']);
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('reports', ReportController::class)->name('reports');
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::middleware('can:super-admin')->group(function () {
            Route::get('insights', InsightController::class)->name('insights');
            Route::resource('users', UserController::class)->except(['show', 'destroy']);
            Route::resource('branches', BranchController::class)->except(['show', 'destroy']);
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
            Route::post('payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
            Route::put('payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        });
        Route::middleware('can:manage,booking')->group(function () {
            Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
            Route::put('bookings/{booking}/status', [BookingStatusController::class, 'update'])->name('bookings.status');
            Route::put('bookings/{booking}/schedule', [BookingRescheduleController::class, 'update'])->name('bookings.reschedule');
            Route::put('bookings/{booking}/treatment', [BookingTreatmentController::class, 'update'])->name('bookings.treatment');
            Route::post('bookings/{booking}/payments', [PaymentController::class, 'store'])->middleware('throttle:30,1')->name('bookings.payments.store');
            Route::post('bookings/{booking}/refunds', [PaymentController::class, 'refund'])->middleware('throttle:30,1')->name('bookings.refunds.store');
            Route::get('bookings/{booking}/receipt', [PaymentController::class, 'receipt'])->name('bookings.receipt');
            Route::put('bookings/{booking}/departure', [BookingDepartureController::class, 'update'])->name('bookings.departure');
        });
        Route::get('home-care', [HomeCareController::class, 'index'])->name('home-care.index');
        Route::post('home-care/areas', [HomeCareController::class, 'storeArea'])->name('home-care.areas.store');
        Route::put('home-care/areas/{area}', [HomeCareController::class, 'updateArea'])->name('home-care.areas.update');
        Route::put('home-care/branches/{branch}/limit', [HomeCareController::class, 'updateLimit'])->name('home-care.limit');
        Route::get('therapists', [TherapistController::class, 'index'])->name('therapists.index');
        Route::get('therapists/create', [TherapistController::class, 'create'])->name('therapists.create');
        Route::post('therapists', [TherapistController::class, 'store'])->name('therapists.store');
        Route::middleware('can:manage,therapist')->group(function () {
            Route::get('therapists/{therapist}/edit', [TherapistController::class, 'edit'])->name('therapists.edit');
            Route::put('therapists/{therapist}', [TherapistController::class, 'update'])->name('therapists.update');
            Route::post('therapists/{therapist}/leaves', [TherapistLeaveController::class, 'store'])->name('therapists.leaves.store');
            Route::delete('therapists/{therapist}/leaves/{leave}', [TherapistLeaveController::class, 'destroy'])->scopeBindings()->name('therapists.leaves.destroy');
        });
        Route::put('treatments/{treatment}/branches', [TreatmentBranchController::class, 'update'])->name('treatments.branches.update');
    });

    Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
});
