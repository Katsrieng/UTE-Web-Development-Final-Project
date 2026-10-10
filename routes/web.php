<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\RoomTypeController;
Route::get('/', function () {
    return redirect('/rooms');
});
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::view('/', 'welcome')->name('welcome');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Rooms & Facilities Routes (Lim Sak)
|--------------------------------------------------------------------------
*/
Route::resource('room-types', RoomTypeController::class);
Route::resource('rooms', RoomController::class);
Route::resource('facilities', FacilityController::class);    

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1'); // 5 tries per minute
});


Route::middleware('auth')->group(function () {
        Route::middleware('role:customer')->group(function () {
        Route::get('/memberships', [App\Http\Controllers\MembershipController::class, 'index'])->name('memberships.index');
        Route::get('/memberships/{membershipType}/checkout', [App\Http\Controllers\MembershipController::class, 'checkout'])->name('memberships.checkout');
        Route::post('/memberships/subscribe', [App\Http\Controllers\MembershipController::class, 'subscribe'])->name('memberships.subscribe');
        Route::post('/memberships/{membership}/cancel', [App\Http\Controllers\MembershipController::class, 'cancel'])->name('memberships.cancel');
    });
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/home', HomeController::class)->name('home');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    /*
    |----------------------------------------------------------------------
    | Staff + Admin only  (your teammates' existing Payment/Dashboard routes)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
            ->name('payments.receipt');

        Route::resource('payments', PaymentController::class);
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
    });

   
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('membership-types', App\Http\Controllers\Admin\MembershipTypeController::class)->except(['show']);
        Route::resource('packages', App\Http\Controllers\Admin\PackageController::class)->except(['show']);
    });
});
