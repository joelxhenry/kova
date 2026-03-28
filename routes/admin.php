<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('admin.dashboard');

Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('admin.users.reactivate');
