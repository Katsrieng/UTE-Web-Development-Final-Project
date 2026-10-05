<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;


// Homepage
Route::view('/', 'welcome')->name('welcome');


// ==========================================
// PUBLIC ROUTES
// ==========================================

// Visitors can view rooms and facilities
Route::resource('room-types', RoomTypeController::class)
    ->only(['index', 'show']);

Route::resource('rooms', RoomController::class)
    ->only(['index', 'show']);

Route::resource('facilities', FacilityController::class)
    ->only(['index', 'show']);


// ==========================================
// GUEST ROUTES
// ==========================================

Route::middleware('guest')->group(function () {

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


    // ======================================
    // ADMIN & STAFF ONLY
    // ======================================

    Route::middleware('role:admin,staff')->group(function () {

        // Room management
        Route::resource('room-types', RoomTypeController::class)
            ->except(['index', 'show']);

        Route::resource('rooms', RoomController::class)
            ->except(['index', 'show']);

        Route::resource('facilities', FacilityController::class)
            ->except(['index', 'show']);


        // Payments
        Route::get(
            '/payments/{payment}/receipt',
            [PaymentController::class, 'receipt']
        )->name('payments.receipt');

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

        });

});

require __DIR__.'/events.php';
