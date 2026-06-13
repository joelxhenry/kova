<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Models\RecurringTransaction;
use App\Models\TransactionCategory;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private readonly RecurringTransactionService $recurringTransactionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $recurring = $user->recurringTransactions()
            ->with(['account:id,name,type', 'transferAccount:id,name,type', 'category:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('next_run_date')
            ->get();

        return Inertia::render('Budget/Recurring/Index', [
            'recurring' => $recurring,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Budget/Recurring/Create', [
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function store(StoreRecurringTransactionRequest $request): RedirectResponse
    {
        $this->recurringTransactionService->create($request->user(), $request->validated());

        return redirect()->route('budget.recurring.index')
            ->with('status', 'Recurring rule created.');
    }

    public function edit(Request $request, RecurringTransaction $recurring): Response
    {
        abort_unless($recurring->user_id === auth()->id(), 403);

        $user = $request->user();

        return Inertia::render('Budget/Recurring/Edit', [
            'recurring' => $recurring,
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurring): RedirectResponse
    {
        abort_unless($recurring->user_id === auth()->id(), 403);

        $this->recurringTransactionService->update($recurring, $request->validated());

        return redirect()->route('budget.recurring.index')
            ->with('status', 'Recurring rule updated.');
    }

    public function destroy(RecurringTransaction $recurring): RedirectResponse
    {
        abort_unless($recurring->user_id === auth()->id(), 403);

        // Cancelling deactivates the rule; previously generated ledger rows stay
        // intact (FR-3.4).
        $this->recurringTransactionService->cancel($recurring);

        return redirect()->route('budget.recurring.index')
            ->with('status', 'Recurring rule cancelled.');
    }

    public function cancel(RecurringTransaction $recurring): RedirectResponse
    {
        abort_unless($recurring->user_id === auth()->id(), 403);

        $this->recurringTransactionService->cancel($recurring);

        return redirect()->route('budget.recurring.index')
            ->with('status', 'Recurring rule cancelled.');
    }
}
