<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StatutoryRateController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('admin.dashboard');

Route::get('/statutory-rates', [StatutoryRateController::class, 'index'])->name('admin.statutory-rates.index');
Route::get('/statutory-rates/{key}', [StatutoryRateController::class, 'show'])->name('admin.statutory-rates.show');
Route::post('/statutory-rates/{key}', [StatutoryRateController::class, 'store'])->name('admin.statutory-rates.store');

Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('admin.users.reactivate');
