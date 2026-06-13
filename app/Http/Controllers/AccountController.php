<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\StoreCreditPaymentRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
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

        return Inertia::render('Budget/Accounts/Index', [
            'accounts' => $accounts,
            'summary' => $this->accountService->summary($accounts),
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

    public function payment(StoreCreditPaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $from = Account::findOrFail($validated['from_account_id']);
        $to = Account::findOrFail($validated['to_account_id']);

        abort_unless($from->user_id === auth()->id() && $to->user_id === auth()->id(), 403);

        $this->accountService->payCredit($from, $to, $validated);

        return redirect()->route('budget.accounts.index')
            ->with('status', 'Payment recorded.');
    }
}
