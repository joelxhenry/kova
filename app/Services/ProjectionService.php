<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\ExpectedTransaction;
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
     *     datasets: list<array{account_id: int, name: string, type: string, points: list<float>, interest_accrued: float}>,
     *     aggregate: list<float>,
     *     alerts: list<array{account_id: int, name: string, date: string, balance: float}>,
     *     starting_net_worth: float,
     *     ending_net_worth: float,
     *     lowest_net_worth: float,
     *     expected_events: list<array{account_id: int|null, name: string, date: string, type: string, amount: float, signed_delta: float}>,
     *     interest: array{cost: float, earned: float, net_worth_impact: float, by_account: list<array{account_id: int, name: string, type: string, accrued: float}>}
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
        // by default, every active account. Note: Eloquent's only() reindexes the
        // collection (array_values), which would corrupt the id-keyed delta
        // lookups below — so filter by membership to keep the id keys intact.
        $tracked = $accountIds !== []
            ? $allAccounts->filter(fn (Account $a): bool => in_array($a->id, $accountIds, true))
            : $allAccounts->filter(fn (Account $a): bool => (bool) $a->is_active);

        $labels = $this->buildLabels($today, $until);

        // Monthly anniversaries of today within the horizon — the dates interest
        // compounds on (B9). Anchored to today so every account accrues in step.
        $accrualSet = array_flip($this->buildAccrualDates($today, $until));

        // Per-account, per-date signed deltas from recurring rules.
        $deltas = $this->simulateDeltas($user, $tracked->keys()->all(), $allAccounts, $today, $until);

        // Fold in pending one-off expected cash flows (B6). Account-bound items
        // join their account series; unassigned items only move the aggregate.
        $expected = $this->simulateExpected($user, $tracked->keys()->all(), $allAccounts, $accountIds, $today, $until);
        foreach ($expected['accountDeltas'] as $accountId => $byDate) {
            foreach ($byDate as $date => $delta) {
                $deltas[$accountId][$date] = round(($deltas[$accountId][$date] ?? 0.0) + $delta, 2);
            }
        }

        $datasets = [];
        $alerts = [];
        $interestRows = [];
        $interestCost = 0.0;
        $interestEarned = 0.0;

        foreach ($tracked as $accountId => $account) {
            $running = (float) $account->current_balance;
            $monthlyRate = $account->monthly_interest_rate;
            $accrued = 0.0;
            $points = [];
            $breached = false;

            foreach ($labels as $label) {
                $running = round($running + ($deltas[$accountId][$label] ?? 0.0), 2);

                // Compound interest on each monthly anniversary (B9). Interest
                // always grows the balance magnitude: more debt on a credit
                // account, more savings on a debit one — so it simply adds to
                // the running balance, which the net-worth aggregate then signs.
                if ($monthlyRate !== null && isset($accrualSet[$label])) {
                    $charge = round($running * $monthlyRate, 2);
                    $running = round($running + $charge, 2);
                    $accrued = round($accrued + $charge, 2);
                }

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
                'interest_accrued' => $accrued,
            ];

            if ($monthlyRate !== null && $accrued !== 0.0) {
                $interestRows[] = [
                    'account_id' => (int) $accountId,
                    'name' => $account->name,
                    'type' => $account->type,
                    'accrued' => $accrued,
                ];

                if ($account->type === 'credit') {
                    $interestCost = round($interestCost + $accrued, 2);
                } else {
                    $interestEarned = round($interestEarned + $accrued, 2);
                }
            }
        }

        $aggregate = $this->aggregateNetWorth($datasets, count($labels));

        // Unassigned expected items (no account) move total cash flow but no
        // single account series; apply their cumulative effect to the aggregate.
        if ($expected['aggregateDeltas'] !== []) {
            $running = 0.0;
            foreach ($labels as $i => $label) {
                $running = round($running + ($expected['aggregateDeltas'][$label] ?? 0.0), 2);
                $aggregate[$i] = round($aggregate[$i] + $running, 2);
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'aggregate' => $aggregate,
            'alerts' => $alerts,
            'starting_net_worth' => $aggregate[0] ?? 0.0,
            'ending_net_worth' => $aggregate !== [] ? $aggregate[count($aggregate) - 1] : 0.0,
            'lowest_net_worth' => $aggregate !== [] ? min($aggregate) : 0.0,
            'expected_events' => $expected['events'],
            'interest' => [
                'cost' => $interestCost,
                'earned' => $interestEarned,
                'net_worth_impact' => round($interestEarned - $interestCost, 2),
                'by_account' => $interestRows,
            ],
        ];
    }

    /**
     * Monthly-anniversary dates of $today within (today, until]. These are the
     * dates interest compounds on; anchored to today's day-of-month so all
     * accounts accrue together (e.g. today 06-13 → 07-13, 08-13, …).
     *
     * @return list<string>
     */
    private function buildAccrualDates(Carbon $today, Carbon $until): array
    {
        $dates = [];

        for ($date = $today->copy()->addMonthNoOverflow(); $date->lte($until); $date->addMonthNoOverflow()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    /**
     * Collect pending one-off expected cash flows whose `expected_date` falls in
     * [today, until] (FR-5.4). Account-bound items contribute a signed delta to
     * their (tracked) account's series; unassigned items contribute only to the
     * aggregate, signed debit-style (income +, expense −). Realized and cancelled
     * items are excluded — realized effects already live in `current_balance`
     * (FR-5.5). Returns tagged events so the UI can mark them distinctly.
     *
     * @param list<int> $trackedIds Account ids included in this projection.
     * @param Collection<int, Account> $allAccounts
     * @param list<int> $accountIds The raw account filter (empty = all accounts).
     * @return array{
     *     accountDeltas: array<int, array<string, float>>,
     *     aggregateDeltas: array<string, float>,
     *     events: list<array{account_id: int|null, name: string, date: string, type: string, amount: float, signed_delta: float}>
     * }
     */
    private function simulateExpected(
        User $user,
        array $trackedIds,
        Collection $allAccounts,
        array $accountIds,
        Carbon $today,
        Carbon $until,
    ): array {
        $accountDeltas = [];
        $aggregateDeltas = [];
        $events = [];
        $trackedSet = array_flip($trackedIds);

        /** @var Collection<int, ExpectedTransaction> $items */
        $items = $user->expectedTransactions()
            ->pending()
            ->whereDate('expected_date', '>=', $today->toDateString())
            ->whereDate('expected_date', '<=', $until->toDateString())
            ->get();

        foreach ($items as $item) {
            $date = $item->expected_date->toDateString();
            $amount = (float) $item->amount;

            // A planned payment (transfer): the funding leg behaves like an
            // expense, the credit destination like income, applied to whichever
            // legs are tracked. Net-worth neutral, mirroring recurring transfers.
            if ($item->type === 'transfer') {
                $touched = false;

                if ($item->account_id !== null && isset($trackedSet[$item->account_id])) {
                    $from = $allAccounts->get($item->account_id);
                    $delta = $this->accountService->signFor($from->type, 'expense') * $amount;
                    $accountDeltas[$item->account_id][$date] = round(($accountDeltas[$item->account_id][$date] ?? 0.0) + $delta, 2);
                    $touched = true;
                }

                if ($item->transfer_account_id !== null && isset($trackedSet[$item->transfer_account_id])) {
                    $to = $allAccounts->get($item->transfer_account_id);
                    $delta = $this->accountService->signFor($to->type, 'income') * $amount;
                    $accountDeltas[$item->transfer_account_id][$date] = round(($accountDeltas[$item->transfer_account_id][$date] ?? 0.0) + $delta, 2);
                    $touched = true;
                }

                if ($touched) {
                    $destination = $item->transfer_account_id !== null ? $allAccounts->get($item->transfer_account_id) : null;
                    $events[] = [
                        'account_id' => $item->transfer_account_id !== null ? (int) $item->transfer_account_id : null,
                        'name' => $destination?->name ?? 'Payment',
                        'date' => $date,
                        'type' => 'transfer',
                        'amount' => $amount,
                        // A transfer between your own accounts does not move net worth.
                        'signed_delta' => 0.0,
                    ];
                }

                continue;
            }

            if ($item->account_id !== null) {
                // Skip items bound to an account outside the tracked set.
                if (! isset($trackedSet[$item->account_id])) {
                    continue;
                }

                /** @var Account $account */
                $account = $allAccounts->get($item->account_id);
                $delta = $this->accountService->signFor($account->type, $item->type) * $amount;

                $accountDeltas[$item->account_id][$date] = round(
                    ($accountDeltas[$item->account_id][$date] ?? 0.0) + $delta,
                    2,
                );

                // Net-worth effect mirrors the aggregate sign (credit debt subtracts).
                $signedDelta = round(($account->type === 'credit' ? -1 : 1) * $delta, 2);

                $events[] = [
                    'account_id' => (int) $item->account_id,
                    'name' => $account->name,
                    'date' => $date,
                    'type' => $item->type,
                    'amount' => $amount,
                    'signed_delta' => $signedDelta,
                ];

                continue;
            }

            // Unassigned items only make sense against the full (all-accounts)
            // view; a per-account filter has no bucket for them.
            if ($accountIds !== []) {
                continue;
            }

            $signedDelta = round(($item->type === 'income' ? 1 : -1) * $amount, 2);
            $aggregateDeltas[$date] = round(($aggregateDeltas[$date] ?? 0.0) + $signedDelta, 2);

            $events[] = [
                'account_id' => null,
                'name' => 'Unassigned',
                'date' => $date,
                'type' => $item->type,
                'amount' => $amount,
                'signed_delta' => $signedDelta,
            ];
        }

        return [
            'accountDeltas' => $accountDeltas,
            'aggregateDeltas' => $aggregateDeltas,
            'events' => $events,
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
