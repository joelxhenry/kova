<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectionService
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly RecurringTransactionService $recurringTransactionService,
    ) {}

    /**
     * Simulate cash-flow forward from today to $until, starting at each account's
     * cached `current_balance` and applying every active recurring occurrence in
     * the window (FR-4.1). Pure read-only — performs NO database writes.
     *
     * @param list<int> $accountIds Restrict the projection to these accounts; empty = all active accounts.
     * @return array{
     *     labels: list<string>,
     *     datasets: list<array{account_id: int, name: string, type: string, points: list<float>}>,
     *     aggregate: list<float>,
     *     alerts: list<array{account_id: int, name: string, date: string, balance: float}>,
     *     starting_net_worth: float,
     *     ending_net_worth: float,
     *     lowest_net_worth: float
     * }
     */
    public function project(User $user, Carbon $until, array $accountIds = []): array
    {
        $today = Carbon::today();
        $until = $until->copy()->startOfDay();

        // Full account map (keyed by id) for type lookups, including transfer
        // counterparts that may sit outside the tracked set.
        /** @var Collection<int, Account> $allAccounts */
        $allAccounts = $user->accounts()->get()->keyBy('id');

        // Tracked accounts: the explicit filter (intersected with ownership) or,
        // by default, every active account.
        $tracked = $accountIds !== []
            ? $allAccounts->only($accountIds)
            : $allAccounts->filter(fn (Account $a): bool => (bool) $a->is_active);

        $labels = $this->buildLabels($today, $until);

        // Per-account, per-date signed deltas from recurring rules.
        $deltas = $this->simulateDeltas($user, $tracked->keys()->all(), $allAccounts, $today, $until);

        $datasets = [];
        $alerts = [];

        foreach ($tracked as $accountId => $account) {
            $running = (float) $account->current_balance;
            $points = [];
            $breached = false;

            foreach ($labels as $label) {
                $running = round($running + ($deltas[$accountId][$label] ?? 0.0), 2);
                $points[] = $running;

                // FR-4.5: flag the first day a debit account dips below zero.
                if (! $breached && $account->type === 'debit' && $running < 0) {
                    $alerts[] = [
                        'account_id' => (int) $accountId,
                        'name' => $account->name,
                        'date' => $label,
                        'balance' => $running,
                    ];
                    $breached = true;
                }
            }

            $datasets[] = [
                'account_id' => (int) $accountId,
                'name' => $account->name,
                'type' => $account->type,
                'points' => $points,
            ];
        }

        $aggregate = $this->aggregateNetWorth($datasets, count($labels));

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'aggregate' => $aggregate,
            'alerts' => $alerts,
            'starting_net_worth' => $aggregate[0] ?? 0.0,
            'ending_net_worth' => $aggregate !== [] ? $aggregate[count($aggregate) - 1] : 0.0,
            'lowest_net_worth' => $aggregate !== [] ? min($aggregate) : 0.0,
        ];
    }

    /**
     * Inclusive list of ISO date strings from $today to $until.
     *
     * @return list<string>
     */
    private function buildLabels(Carbon $today, Carbon $until): array
    {
        $labels = [];

        for ($date = $today->copy(); $date->lte($until); $date->addDay()) {
            $labels[] = $date->toDateString();
        }

        return $labels;
    }

    /**
     * Walk every active recurring rule that touches a tracked account and record
     * its signed balance effect per occurrence date within [today, until].
     *
     * @param list<int> $trackedIds
     * @param Collection<int, Account> $allAccounts
     * @return array<int, array<string, float>> [accountId][date] => delta
     */
    private function simulateDeltas(
        User $user,
        array $trackedIds,
        Collection $allAccounts,
        Carbon $today,
        Carbon $until,
    ): array {
        $deltas = [];

        if ($trackedIds === []) {
            return $deltas;
        }

        $trackedSet = array_flip($trackedIds);

        /** @var Collection<int, RecurringTransaction> $rules */
        $rules = $user->recurringTransactions()
            ->active()
            ->where(function ($query) use ($trackedIds): void {
                $query->whereIn('account_id', $trackedIds)
                    ->orWhereIn('transfer_account_id', $trackedIds);
            })
            ->get();

        foreach ($rules as $rule) {
            $occurrence = $rule->next_run_date->copy();

            // Fast-forward past any overdue (ungenerated) occurrences: the
            // projection starts from current_balance, which reflects only what
            // has already posted (FR-4.1 — simulate from today forward).
            while ($occurrence->lt($today)) {
                $occurrence = $this->recurringTransactionService->nextDate($rule, $occurrence);
            }

            while (
                $occurrence->lte($until)
                && ($rule->end_date === null || $occurrence->lte($rule->end_date))
            ) {
                $date = $occurrence->toDateString();

                if ($rule->type === 'transfer') {
                    // Source leg behaves like an expense, destination like income.
                    $this->record($deltas, $trackedSet, $allAccounts, $rule->account_id, (float) $rule->amount, 'expense', $date);
                    if ($rule->transfer_account_id !== null) {
                        $this->record($deltas, $trackedSet, $allAccounts, $rule->transfer_account_id, (float) $rule->amount, 'income', $date);
                    }
                } else {
                    $this->record($deltas, $trackedSet, $allAccounts, $rule->account_id, (float) $rule->amount, $rule->type, $date);
                }

                $occurrence = $this->recurringTransactionService->nextDate($rule, $occurrence);
            }
        }

        return $deltas;
    }

    /**
     * Add one occurrence's signed effect to the delta map, but only for accounts
     * inside the tracked set.
     *
     * @param array<int, array<string, float>> $deltas
     * @param array<int, int> $trackedSet
     * @param Collection<int, Account> $allAccounts
     */
    private function record(
        array &$deltas,
        array $trackedSet,
        Collection $allAccounts,
        int $accountId,
        float $amount,
        string $type,
        string $date,
    ): void {
        if (! isset($trackedSet[$accountId])) {
            return;
        }

        $account = $allAccounts->get($accountId);
        if ($account === null) {
            return;
        }

        $delta = $this->accountService->signFor($account->type, $type) * $amount;

        $deltas[$accountId][$date] = round(($deltas[$accountId][$date] ?? 0.0) + $delta, 2);
    }

    /**
     * Net-worth series across the datasets: debit balances add, credit balances
     * (debt) subtract, matching AccountService::summary (FR-4.2/4.3).
     *
     * @param list<array{account_id: int, name: string, type: string, points: list<float>}> $datasets
     * @return list<float>
     */
    private function aggregateNetWorth(array $datasets, int $length): array
    {
        $aggregate = array_fill(0, $length, 0.0);

        foreach ($datasets as $dataset) {
            $sign = $dataset['type'] === 'credit' ? -1 : 1;

            foreach ($dataset['points'] as $i => $point) {
                $aggregate[$i] = round($aggregate[$i] + ($sign * $point), 2);
            }
        }

        return $aggregate;
    }
}
