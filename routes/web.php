<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ExpectedTransactionController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('landing');
})->name('landing');

// Guest routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:3,1');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::put('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])->name('invoices.pdf');

    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::get('/reports', ReportController::class)->name('reports');

    // Budgeting module — accounts, transactions, recurring rules, and projections.
    // All budget routes live under the `budget/` prefix and `budget.` name space;
    // individual resources are registered per phase (B1–B5).
    Route::prefix('budget')->name('budget.')->group(function (): void {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::resource('accounts', AccountController::class)->except(['show']);
        Route::post('transfers', [AccountController::class, 'transfer'])->name('transfers.store');
        Route::resource('transactions', TransactionController::class)->except(['show']);
        Route::resource('recurring', RecurringTransactionController::class)->except(['show']);
        Route::post('recurring/{recurring}/cancel', [RecurringTransactionController::class, 'cancel'])->name('recurring.cancel');
        Route::resource('expected', ExpectedTransactionController::class)->except(['show']);
        Route::post('expected/{expected}/realize', [ExpectedTransactionController::class, 'realize'])->name('expected.realize');
        Route::post('expected/{expected}/cancel', [ExpectedTransactionController::class, 'cancel'])->name('expected.cancel');
        Route::get('projections', [ProjectionController::class, 'index'])->name('projections.index');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business');
    Route::put('/settings/invoicing', [SettingsController::class, 'updateInvoicing'])->name('settings.invoicing');
    Route::put('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
