<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StatutoryRateController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('admin.dashboard');

Route::get('/statutory-rates', [StatutoryRateController::class, 'index'])->name('admin.statutory-rates.index');
Route::get('/statutory-rates/{key}', [StatutoryRateController::class, 'show'])->name('admin.statutory-rates.show');
Route::post('/statutory-rates/{key}', [StatutoryRateController::class, 'store'])->name('admin.statutory-rates.store');
