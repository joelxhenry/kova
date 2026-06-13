<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\StoreCreditPaymentRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\ExpectedTransactionService;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(Request $request): Response
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Active recurring payments (transfers into a credit account) so each
        // credit card can show — and manage — its scheduled payments inline.
        $scheduledPayments = $request->user()
            ->recurringTransactions()
            ->active()
            ->where('type', 'transfer')
            ->whereIn('transfer_account_id', $accounts->where('type', 'credit')->pluck('id'))
            ->with('account:id,name')
            ->orderBy('next_run_date')
            ->get(['id', 'account_id', 'transfer_account_id', 'amount', 'frequency', 'next_run_date', 'end_date', 'description']);

        return Inertia::render('Budget/Accounts/Index', [
            'accounts' => $accounts,
            'summary' => $this->accountService->summary($accounts),
            'scheduledPayments' => $scheduledPayments,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Budget/Accounts/Create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $this->accountService->create($request->user(), $request->validated());

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Account created.');
    }

    public function edit(Account $account): Response
    {
        abort_unless($account->user_id === auth()->id(), 403);

        return Inertia::render('Budget/Accounts/Edit', [
            'account' => $account,
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        abort_unless($account->user_id === auth()->id(), 403);

        $this->accountService->update($account, $request->validated());

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        abort_unless($account->user_id === auth()->id(), 403);

        try {
            $this->accountService->delete($account);
        } catch (RuntimeException $e) {
            return redirect()->route('budget.accounts.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Account deleted.');
    }

    public function transfer(StoreTransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $from = Account::findOrFail($validated['from_account_id']);
        $to = Account::findOrFail($validated['to_account_id']);

        abort_unless($from->user_id === auth()->id() && $to->user_id === auth()->id(), 403);

        $this->accountService->transfer($from, $to, $validated);

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Transfer recorded.');
    }

    public function payment(
        StoreCreditPaymentRequest $request,
        RecurringTransactionService $recurringService,
        ExpectedTransactionService $expectedService,
    ): RedirectResponse {
        $validated = $request->validated();

        $from = Account::findOrFail($validated['from_account_id']);
        $to = Account::findOrFail($validated['to_account_id']);

        abort_unless($from->user_id === auth()->id() && $to->user_id === auth()->id(), 403);

        $schedule = $validated['schedule'] ?? 'now';

        // A recurring payment is scheduled as a recurring debit→credit transfer
        // so the existing recurring engine settles and projects it.
        if ($schedule === 'recurring') {
            $recurringService->create($request->user(), [
                'account_id' => $from->id,
                'transfer_account_id' => $to->id,
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'frequency' => $validated['frequency'],
                'start_date' => $validated['date'],
                'end_date' => $validated['end_date'] ?? null,
                'description' => $validated['description'] ?? 'Credit card payment',
            ]);

            return redirect()->route('budget.accounts.index')
                ->with('status', 'Recurring payment scheduled.');
        }

        // A planned payment is a forecast-only expected transfer until realized.
        if ($schedule === 'expected') {
            $expectedService->create($request->user(), [
                'account_id' => $from->id,
                'transfer_account_id' => $to->id,
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'expected_date' => $validated['date'],
                'description' => $validated['description'] ?? 'Credit card payment',
            ]);

            return redirect()->route('budget.accounts.index')
                ->with('status', 'Planned payment added.');
        }

        $this->accountService->payCredit($from, $to, $validated);

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Payment recorded.');
    }
}
