<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\WithholdingCreditController;
use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TaxProfileController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/tax-profile', [TaxProfileController::class, 'edit'])->name('tax-profile.edit');
    Route::put('/tax-profile', [TaxProfileController::class, 'update'])->name('tax-profile.update');

    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('income', IncomeEntryController::class)->except(['show'])->parameters(['income' => 'income_entry']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::get('/withholding-credits', [WithholdingCreditController::class, 'index'])->name('withholding-credits.index');
    Route::post('/withholding-credits', [WithholdingCreditController::class, 'store'])->name('withholding-credits.store');
    Route::delete('/withholding-credits/{withholding_credit}', [WithholdingCreditController::class, 'destroy'])->name('withholding-credits.destroy');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
