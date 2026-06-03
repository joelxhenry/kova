<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecurringTransactionService
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly AccountService $accountService,
    ) {}

    /**
     * Persist a recurring rule. The first occurrence is scheduled on the
     * start date; the scheduler (or a catch-up run) materializes it (FR-3.1/3.2).
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): RecurringTransaction
    {
        // The first run lands on the start date until the engine advances it.
        $data['next_run_date'] = $data['start_date'];
        $data['is_active'] = $data['is_active'] ?? true;
        $data['last_run_date'] = null;

        return $user->recurringTransactions()->create($data);
    }

    /**
     * Edit a rule. Already-generated ledger transactions are NEVER touched
     * (FR-3.4) — only future occurrences are affected. If the start date moves
     * before any occurrence has run, the next run is re-anchored to it.
     *
     * @param array<string, mixed> $data
     */
    public function update(RecurringTransaction $rule, array $data): RecurringTransaction
    {
        // Re-anchor the schedule only while nothing has been generated yet.
        if (array_key_exists('start_date', $data) && $rule->last_run_date === null) {
            $data['next_run_date'] = $data['start_date'];
        }

        $rule->update($data);

        return $rule;
    }

    /**
     * Cancel a rule by deactivating it. Prior generated transactions remain in
     * the ledger and balances are left untouched (FR-3.4).
     */
    public function cancel(RecurringTransaction $rule): void
    {
        $rule->update(['is_active' => false]);
    }

    /**
     * The next occurrence date after $from, stepping by the rule's frequency.
     * Month/year stepping clamps to the end of shorter months (Jan 31 → Feb 28).
     */
    public function nextDate(RecurringTransaction $rule, Carbon $from): Carbon
    {
        return match ($rule->frequency) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'biweekly' => $from->copy()->addWeeks(2),
            'monthly' => $from->copy()->addMonthNoOverflow(),
            'yearly' => $from->copy()->addYearNoOverflow(),
            default => throw new InvalidArgumentException("Unknown frequency: {$rule->frequency}"),
        };
    }

    /**
     * Materialize every active rule whose next run is due on/before $asOf,
     * generating each missed occurrence in sequence (catch-up safe) and advancing
     * the schedule (FR-3.2/3.3). Idempotent: a second run within the same window
     * finds the schedule already advanced and generates nothing.
     */
    public function generateDue(?Carbon $asOf = null): int
    {
        $cutoff = ($asOf ?? Carbon::now())->copy()->startOfDay();

        $count = 0;

        $rules = RecurringTransaction::query()
            ->active()
            ->where('next_run_date', '<=', $cutoff)
            ->get();

        foreach ($rules as $rule) {
            $count += $this->run($rule, $cutoff);
        }

        return $count;
    }

    /**
     * Generate all due occurrences for a single rule up to the cutoff, advancing
     * next_run_date / last_run_date after each. Stops at the end date when set.
     */
    private function run(RecurringTransaction $rule, Carbon $cutoff): int
    {
        $generated = 0;

        while (
            $rule->is_active
            && $rule->next_run_date->lte($cutoff)
            && ($rule->end_date === null || $rule->next_run_date->lte($rule->end_date))
        ) {
            $this->generateOccurrence($rule);

            $rule->last_run_date = $rule->next_run_date->copy();
            $rule->next_run_date = $this->nextDate($rule, $rule->next_run_date);
            $rule->save();

            $generated++;
        }

        return $generated;
    }

    /**
     * Create a single ledger transaction for the rule's current next_run_date,
     * stamped with its provenance and applying the correct balance effect.
     */
    private function generateOccurrence(RecurringTransaction $rule): Transaction
    {
        $data = [
            'account_id' => $rule->account_id,
            'transfer_account_id' => $rule->transfer_account_id,
            'transaction_category_id' => $rule->transaction_category_id,
            'type' => $rule->type,
            'amount' => $rule->amount,
            'date' => $rule->next_run_date->toDateString(),
            'description' => $rule->description,
            'recurring_transaction_id' => $rule->id,
        ];

        if ($rule->type === 'transfer') {
            return $this->generateTransfer($rule, $data);
        }

        // Income/expense entries route through TransactionService so the cached
        // balance is mutated through the single applyDelta path (FR-3.3).
        return $this->transactionService->create($rule->user, $data);
    }

    /**
     * A recurring transfer: one `type=transfer` row adjusting both legs by their
     * own sign rules, never counted as income or expense.
     *
     * @param array<string, mixed> $data
     */
    private function generateTransfer(RecurringTransaction $rule, array $data): Transaction
    {
        return DB::transaction(function () use ($rule, $data): Transaction {
            $transaction = Transaction::create($data);

            $from = Account::findOrFail($rule->account_id);
            $this->accountService->applyDelta($from, (float) $rule->amount, 'expense');

            if ($rule->transfer_account_id !== null) {
                $to = Account::findOrFail($rule->transfer_account_id);
                $this->accountService->applyDelta($to, (float) $rule->amount, 'income');
            }

            return $transaction;
        });
    }
}
