<?php

use App\Http\Controllers\Admin\MembershipTypeController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomImageController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Middleware\EnsureActiveCustomer;
use Illuminate\Support\Facades\Route;

// Homepage
Route::view('/', 'welcome')->name('welcome');

// ==========================================
// PUBLIC ROUTES
// ==========================================

// Visitors can view rooms and facilities
Route::resource('room-types', RoomTypeController::class)
    ->only(['index', 'show'])
    ->whereNumber('room_type');

Route::resource('rooms', RoomController::class)
    ->only(['index', 'show'])
    ->whereNumber('room');

Route::resource('facilities', FacilityController::class)
    ->only(['index', 'show'])
    ->whereNumber('facility');

// ==========================================
// GUEST ROUTES
// ==========================================

Route::middleware('guest')->group(function () {
    Route::get('/staff/login', [LoginController::class, 'staffCreate'])->name('staff.login');
    Route::post('/staff/login', [LoginController::class, 'staffStore'])->middleware('throttle:5,1')->name('staff.login.store');

    Route::get('/register', [RegisterController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1');

});

// ==========================================
// AUTHENTICATED USERS
// ==========================================

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/home', HomeController::class)
        ->name('home');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');

    Route::middleware('role:customer')->group(function () {
        Route::get('/memberships', [MembershipController::class, 'index'])
            ->name('memberships.index');
        Route::post('/memberships/subscribe', [MembershipController::class, 'subscribe'])
            ->name('memberships.subscribe');
        Route::post('/memberships/{membership}/cancel', [MembershipController::class, 'cancel'])
            ->name('memberships.cancel');
    });

    Route::middleware(['role:customer', EnsureActiveCustomer::class])->name('customer.bookings.')->group(function () {
        Route::get('/rooms/{room}/book', [CustomerBookingController::class, 'create'])->name('create');
        Route::post('/rooms/{room}/availability', [CustomerBookingController::class, 'availability'])->name('availability');
        Route::post('/rooms/{room}/book', [CustomerBookingController::class, 'store'])->name('store');
        Route::get('/my-bookings', [CustomerBookingController::class, 'index'])->name('index');
        Route::get('/my-bookings/{booking}', [CustomerBookingController::class, 'show'])->name('show');
        Route::patch('/my-bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('cancel');
    });

    // ======================================
    // ADMIN & STAFF ONLY
    // ======================================

    Route::middleware('role:admin,staff')->group(function () {

        // Room management uses dedicated URLs so public catalogue links keep
        // the public navigation even for signed-in admin and staff users.
        Route::prefix('management')->name('management.')->group(function () {
            Route::post('rooms/{room}/images', [RoomImageController::class, 'store'])->name('rooms.images.store');
            Route::patch('rooms/{room}/images/{roomImage}/primary', [RoomImageController::class, 'primary'])->name('rooms.images.primary');
            Route::patch('rooms/{room}/images/{roomImage}', [RoomImageController::class, 'update'])->name('rooms.images.update');
            Route::delete('rooms/{room}/images/{roomImage}', [RoomImageController::class, 'destroy'])->name('rooms.images.destroy');
            Route::resource('room-types', RoomTypeController::class);
            Route::resource('rooms', RoomController::class);
            Route::resource('facilities', FacilityController::class);
        });

        // Booking management
        Route::patch('/bookings/{id}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::patch('/bookings/{id}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
        Route::patch('/bookings/{id}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out');
        Route::resource('bookings', BookingController::class);

        // Payments
        Route::get(
            '/payments/{payment}/receipt',
            [PaymentController::class, 'receipt']
        )->name('payments.receipt');

        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])
            ->name('payments.refund');

        Route::resource('payments', PaymentController::class);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

    });

    // ======================================
    // ADMIN ONLY
    // ======================================

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('users', UserController::class)
                ->except('show');

            Route::resource('membership-types', MembershipTypeController::class)
                ->except('show');

            Route::resource('packages', PackageController::class)
                ->except('show');

        });

});

require __DIR__.'/events.php';
