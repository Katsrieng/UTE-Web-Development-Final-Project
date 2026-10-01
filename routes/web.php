<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\RoomTypeController;
Route::get('/', function () {
    return redirect('/rooms');
});

Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
    ->name('payments.receipt');

Route::resource('payments', PaymentController::class);

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
