<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaxFormController;
use App\Http\Controllers\WithholdingCreditController;
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

    // Legacy tax-profile routes redirect to settings
    Route::get('/tax-profile', fn () => redirect()->route('settings.index'))->name('tax-profile.edit');
    Route::put('/tax-profile', [SettingsController::class, 'updateTaxProfile'])->name('tax-profile.update');

    Route::resource('clients', ClientController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::put('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])->name('invoices.pdf');

Route::get('/withholding-credits', [WithholdingCreditController::class, 'index'])->name('withholding-credits.index');
    Route::post('/withholding-credits', [WithholdingCreditController::class, 'store'])->name('withholding-credits.store');
    Route::delete('/withholding-credits/{withholding_credit}', [WithholdingCreditController::class, 'destroy'])->name('withholding-credits.destroy');

    Route::get('/tax-form', [TaxFormController::class, 'show'])->name('tax-form.show');
    Route::post('/tax-form/generate', [TaxFormController::class, 'generate'])->name('tax-form.generate');
    Route::get('/tax-form/snapshot/{snapshot}', [TaxFormController::class, 'snapshot'])->name('tax-form.snapshot');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business');
    Route::put('/settings/invoicing', [SettingsController::class, 'updateInvoicing'])->name('settings.invoicing');
    Route::put('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email');
    Route::put('/settings/tax-profile', [SettingsController::class, 'updateTaxProfile'])->name('settings.tax-profile');
    Route::delete('/settings/logo', [SettingsController::class, 'removeLogo'])->name('settings.logo.remove');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
