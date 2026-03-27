<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeEntryRequest;
use App\Models\IncomeEntry;
use App\Services\IncomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeEntryController extends Controller
{
    public function __construct(
        private readonly IncomeService $incomeService,
    ) {}

    public function index(Request $request): Response
    {
        $entries = $request->user()
            ->incomeEntries()
            ->orderByDesc('date_received')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Income/Index', [
            'entries' => $entries,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Income/Create');
    }

    public function store(StoreIncomeEntryRequest $request): RedirectResponse
    {
        $this->incomeService->create($request->user(), $request->validated());

        return redirect()->route('income.index')
            ->with('status', 'Income entry created.');
    }

    public function edit(IncomeEntry $income_entry): Response
    {
        abort_unless($income_entry->user_id === auth()->id(), 403);

        return Inertia::render('Income/Edit', [
            'entry' => $income_entry,
        ]);
    }

    public function update(StoreIncomeEntryRequest $request, IncomeEntry $income_entry): RedirectResponse
    {
        abort_unless($income_entry->user_id === auth()->id(), 403);

        $this->incomeService->update($income_entry, $request->validated());

        return redirect()->route('income.index')
            ->with('status', 'Income entry updated.');
    }

    public function destroy(IncomeEntry $income_entry): RedirectResponse
    {
        abort_unless($income_entry->user_id === auth()->id(), 403);

        $this->incomeService->delete($income_entry);

        return redirect()->route('income.index')
            ->with('status', 'Income entry deleted.');
    }
}
