<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RealizeExpectedTransactionRequest;
use App\Http\Requests\StoreExpectedTransactionRequest;
use App\Http\Requests\UpdateExpectedTransactionRequest;
use App\Models\ExpectedTransaction;
use App\Models\TransactionCategory;
use App\Services\ExpectedTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ExpectedTransactionController extends Controller
{
    public function __construct(
        private readonly ExpectedTransactionService $expectedTransactionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $user->expectedTransactions()
            ->with(['account:id,name,type', 'transferAccount:id,name,type', 'category:id,name'])
            ->orderBy('expected_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        return Inertia::render('Budget/Expected/Index', [
            'expected' => $query->get(),
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Budget/Expected/Create', [
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function store(StoreExpectedTransactionRequest $request): RedirectResponse
    {
        $this->expectedTransactionService->create($request->user(), $request->validated());

        return redirect()->route('budget.expected.index')
            ->with('status', 'Expected item recorded.');
    }

    public function edit(Request $request, ExpectedTransaction $expected): Response
    {
        abort_unless($expected->user_id === auth()->id(), 403);

        $user = $request->user();

        return Inertia::render('Budget/Expected/Edit', [
            'expected' => $expected,
            'accounts' => $user->accounts()->orderBy('name')->get(['id', 'name', 'type']),
            'categories' => TransactionCategory::forUser($user->id),
        ]);
    }

    public function update(UpdateExpectedTransactionRequest $request, ExpectedTransaction $expected): RedirectResponse
    {
        abort_unless($expected->user_id === auth()->id(), 403);

        $this->expectedTransactionService->update($expected, $request->validated());

        return redirect()->route('budget.expected.index')
            ->with('status', 'Expected item updated.');
    }

    public function destroy(ExpectedTransaction $expected): RedirectResponse
    {
        abort_unless($expected->user_id === auth()->id(), 403);

        $this->expectedTransactionService->delete($expected);

        return redirect()->route('budget.expected.index')
            ->with('status', 'Expected item deleted.');
    }

    /**
     * Realize a pending expected item into a real ledger transaction (FR-5.3).
     */
    public function realize(RealizeExpectedTransactionRequest $request, ExpectedTransaction $expected): RedirectResponse
    {
        abort_unless($expected->user_id === auth()->id(), 403);

        try {
            $this->expectedTransactionService->realize($expected, $request->validated());
        } catch (RuntimeException $e) {
            return redirect()->route('budget.expected.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('budget.expected.index')
            ->with('status', 'Expected item realized — a transaction was posted.');
    }

    public function cancel(ExpectedTransaction $expected): RedirectResponse
    {
        abort_unless($expected->user_id === auth()->id(), 403);

        $this->expectedTransactionService->cancel($expected);

        return redirect()->route('budget.expected.index')
            ->with('status', 'Expected item cancelled.');
    }
}
