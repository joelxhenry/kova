<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // The ledger covers income/expense entries across every account (FR-2.5).
        // Transfers are managed on the Accounts page and excluded here.
        $query = $user->transactions()
            ->with(['account:id,name,type', 'category:id,name'])
            ->whereIn('type', ['income', 'expense'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->integer('account_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('transaction_category_id', $request->integer('category_id'));
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->input('to'));
        }

        return Inertia::render('Budget/Transactions/Index', [
            'transactions' => $query->paginate(20)->withQueryString(),
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
            'filters' => $request->only(['account_id', 'type', 'category_id', 'from', 'to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Budget/Transactions/Create', [
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $this->transactionService->create($request->user(), $request->validated());

        return redirect()->route('budget.transactions.index')
            ->with('status', 'Transaction recorded.');
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

        $user = $request->user();

        return Inertia::render('Budget/Transactions/Edit', [
            'transaction' => $transaction,
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

        $this->transactionService->update($transaction, $request->validated());

        return redirect()->route('budget.transactions.index')
            ->with('status', 'Transaction updated.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === auth()->id(), 403);

        $this->transactionService->delete($transaction);

        return redirect()->route('budget.transactions.index')
            ->with('status', 'Transaction deleted.');
    }
}
