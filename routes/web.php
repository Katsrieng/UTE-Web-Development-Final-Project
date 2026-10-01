<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;

Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
    ->name('payments.receipt');

Route::resource('payments', PaymentController::class);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');
