<?php

use App\Http\Controllers\EventReservationController;
use App\Http\Controllers\Management\EventReservationController as ManagementEventReservationController;
use App\Http\Controllers\Management\VenueController as ManagementVenueController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::resource('venues', VenueController::class)
    ->only(['index', 'show']);

Route::middleware(['auth', 'role:customer'])
    ->prefix('event-reservations')
    ->name('event-reservations.')
    ->group(function (): void {
        Route::get('/', [EventReservationController::class, 'index'])->name('index');
        Route::get('/create', [EventReservationController::class, 'create'])->name('create');
        Route::post('/', [EventReservationController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('/{eventBooking}', [EventReservationController::class, 'show'])->name('show');
        Route::patch('/{eventBooking}/cancel', [EventReservationController::class, 'cancel'])->name('cancel');
    });

Route::middleware(['auth', 'role:admin,staff'])
    ->prefix('management')
    ->name('management.')
    ->group(function (): void {
        Route::resource('venues', ManagementVenueController::class);

        Route::get('/event-reservations', [ManagementEventReservationController::class, 'index'])
            ->name('event-reservations.index');
        Route::get('/event-reservations/{eventBooking}', [ManagementEventReservationController::class, 'show'])
            ->name('event-reservations.show');
        Route::patch('/event-reservations/{eventBooking}/approve', [ManagementEventReservationController::class, 'approve'])
            ->name('event-reservations.approve');
        Route::patch('/event-reservations/{eventBooking}/reject', [ManagementEventReservationController::class, 'reject'])
            ->name('event-reservations.reject');
        Route::patch('/event-reservations/{eventBooking}/cancel', [ManagementEventReservationController::class, 'cancel'])
            ->name('event-reservations.cancel');
    });
