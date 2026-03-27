<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function index(Request $request): Response
    {
        $query = $request->user()
            ->expenses()
            ->with('category:id,name')
            ->orderByDesc('date_incurred');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->input('category_id'));
        }

        if ($request->filled('from')) {
            $query->where('date_incurred', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('date_incurred', '<=', $request->input('to'));
        }

        $expenses = $query->paginate(20)->withQueryString();

        $categories = ExpenseCategory::forUser($request->user()->id);

        $totals = $request->user()
            ->expenses()
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->when($request->filled('from'), fn ($q) => $q->where('date_incurred', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date_incurred', '<=', $request->input('to')))
            ->groupBy('expense_category_id')
            ->pluck('total', 'expense_category_id');

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'totals' => $totals,
            'filters' => $request->only(['category_id', 'from', 'to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $categories = ExpenseCategory::forUser($request->user()->id);

        return Inertia::render('Expenses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $receipt = $request->file('receipt');
        unset($validated['receipt']);

        $this->expenseService->create($request->user(), $validated, $receipt);

        return redirect()->route('expenses.index')
            ->with('status', 'Expense recorded.');
    }

    public function edit(Request $request, Expense $expense): Response
    {
        abort_unless($expense->user_id === auth()->id(), 403);

        $categories = ExpenseCategory::forUser($request->user()->id);

        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
            'categories' => $categories,
        ]);
    }

    public function update(StoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        abort_unless($expense->user_id === auth()->id(), 403);

        $validated = $request->validated();
        $receipt = $request->file('receipt');
        unset($validated['receipt']);

        $this->expenseService->update($expense, $validated, $receipt);

        return redirect()->route('expenses.index')
            ->with('status', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_unless($expense->user_id === auth()->id(), 403);

        $this->expenseService->delete($expense);

        return redirect()->route('expenses.index')
            ->with('status', 'Expense deleted.');
    }
}
