<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BudgetTarget;
use App\Models\TransactionCategory;
use App\Models\User;
use Carbon\Carbon;

class BudgetTargetService
{
    /**
     * Persist a planned amount for a category & period. A target is a pure
     * planning row — it never touches a balance or a transaction.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): BudgetTarget
    {
        return $user->budgetTargets()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(BudgetTarget $target, array $data): BudgetTarget
    {
        $target->update($data);

        return $target;
    }

    public function delete(BudgetTarget $target): void
    {
        $target->delete();
    }

    /**
     * Budget-vs-Actual for a calendar month (FR-6.2/6.3). "Actuals" are derived
     * live from the ledger — never stored — by summing matching-type transactions
     * per category within the month window, excluding internal transfers. Targeted
     * categories are reported first; categories that had activity but no target are
     * appended so nothing is silently dropped.
     *
     * @return array{
     *     month: string,
     *     rows: list<array{category_id:int,name:string,type:string,planned:float,actual:float,variance:float,percent:float,over:bool,targeted:bool}>,
     *     totals: array{planned:float,actual:float,variance:float}
     * }
     */
    public function report(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $targets = $user->budgetTargets()
            ->with('category:id,name')
            ->where('period', 'monthly')
            ->get();

        // Actual income/spend per [category, type] this month — transfers excluded
        // so internal money movements never count against a category budget.
        $actualRows = $user->transactions()
            ->whereNotNull('transaction_category_id')
            ->where('type', '!=', 'transfer')
            ->whereBetween('date', [$start, $end])
            ->selectRaw('transaction_category_id, type, SUM(amount) as total')
            ->groupBy('transaction_category_id', 'type')
            ->get();

        /** @var array<int, array<string, float>> $actualMap */
        $actualMap = [];
        foreach ($actualRows as $row) {
            $actualMap[(int) $row->transaction_category_id][$row->type] = (float) $row->total;
        }

        $rows = [];
        $targetedKeys = [];
        $totalPlanned = 0.0;
        $totalActual = 0.0;

        foreach ($targets as $target) {
            $planned = (float) $target->amount;
            $actual = $actualMap[$target->transaction_category_id][$target->type] ?? 0.0;
            $targetedKeys["{$target->transaction_category_id}-{$target->type}"] = true;

            $rows[] = [
                'category_id' => (int) $target->transaction_category_id,
                'name' => $target->category?->name ?? 'Uncategorised',
                'type' => $target->type,
                'planned' => $planned,
                'actual' => $actual,
                'variance' => round($planned - $actual, 2),
                'percent' => $planned > 0.0 ? round($actual / $planned * 100, 1) : 0.0,
                'over' => $actual > $planned,
                'targeted' => true,
            ];

            $totalPlanned += $planned;
            $totalActual += $actual;
        }

        // Categories with activity this month but no target — surfaced, not hidden.
        $untargetedIds = [];
        foreach ($actualMap as $categoryId => $byType) {
            foreach (array_keys($byType) as $type) {
                if (! isset($targetedKeys["{$categoryId}-{$type}"])) {
                    $untargetedIds[$categoryId] = true;
                }
            }
        }

        $names = TransactionCategory::query()
            ->whereIn('id', array_keys($untargetedIds))
            ->pluck('name', 'id');

        foreach ($actualMap as $categoryId => $byType) {
            foreach ($byType as $type => $total) {
                if (isset($targetedKeys["{$categoryId}-{$type}"])) {
                    continue;
                }

                $rows[] = [
                    'category_id' => (int) $categoryId,
                    'name' => $names[$categoryId] ?? 'Uncategorised',
                    'type' => $type,
                    'planned' => 0.0,
                    'actual' => (float) $total,
                    'variance' => round(-(float) $total, 2),
                    'percent' => 0.0,
                    'over' => false,
                    'targeted' => false,
                ];
            }
        }

        return [
            'month' => $month->format('Y-m'),
            'rows' => $rows,
            'totals' => [
                'planned' => round($totalPlanned, 2),
                'actual' => round($totalActual, 2),
                'variance' => round($totalPlanned - $totalActual, 2),
            ],
        ];
    }
}
