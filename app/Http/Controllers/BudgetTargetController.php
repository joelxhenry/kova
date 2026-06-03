<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetTargetRequest;
use App\Http\Requests\UpdateBudgetTargetRequest;
use App\Models\BudgetTarget;
use App\Models\TransactionCategory;
use App\Services\BudgetTargetService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetTargetController extends Controller
{
    public function __construct(
        private readonly BudgetTargetService $budgetTargetService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $month = $this->resolveMonth($request->input('month'));

        return Inertia::render('Budget/Targets/Index', [
            'report' => $this->budgetTargetService->report($user, $month),
            'targets' => $user->budgetTargets()
                ->with('category:id,name')
                ->orderBy('type')
                ->get(),
            'categories' => TransactionCategory::forUser($user->id),
            'month' => $month->format('Y-m'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Budget/Targets/Create', [
            'categories' => TransactionCategory::forUser($request->user()->id),
        ]);
    }

    public function store(StoreBudgetTargetRequest $request): RedirectResponse
    {
        $this->budgetTargetService->create($request->user(), $request->validated());

        return redirect()->route('budget.targets.index')
            ->with('status', 'Budget target saved.');
    }

    public function edit(Request $request, BudgetTarget $target): Response
    {
        abort_unless($target->user_id === auth()->id(), 403);

        return Inertia::render('Budget/Targets/Edit', [
            'target' => $target,
            'categories' => TransactionCategory::forUser($request->user()->id),
        ]);
    }

    public function update(UpdateBudgetTargetRequest $request, BudgetTarget $target): RedirectResponse
    {
        abort_unless($target->user_id === auth()->id(), 403);

        $this->budgetTargetService->update($target, $request->validated());

        return redirect()->route('budget.targets.index')
            ->with('status', 'Budget target updated.');
    }

    public function destroy(BudgetTarget $target): RedirectResponse
    {
        abort_unless($target->user_id === auth()->id(), 403);

        $this->budgetTargetService->delete($target);

        return redirect()->route('budget.targets.index')
            ->with('status', 'Budget target deleted.');
    }

    /**
     * Resolve the calendar month to report on from a `YYYY-MM` query param,
     * falling back to the current month (FR-6.4).
     */
    private function resolveMonth(?string $value): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m-d', "{$value}-01")->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }
}
