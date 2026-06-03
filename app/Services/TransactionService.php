<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    /**
     * Persist an income/expense entry and apply its signed effect to the
     * account's cached balance atomically (FR-2.3 / FR-2.4).
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $transaction = $user->transactions()->create($data);

            $account = Account::findOrFail($transaction->account_id);
            $this->accountService->applyDelta($account, (float) $transaction->amount, $transaction->type);

            return $transaction;
        });
    }

    /**
     * Update an entry, re-deriving cached balances by reversing the original
     * effect before applying the new one. Correctly handles a change of
     * account, amount, or type.
     *
     * @param array<string, mixed> $data
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data): Transaction {
            // Reverse the original effect on the original account first.
            $originalAccount = Account::findOrFail($transaction->account_id);
            $this->accountService->applyDelta(
                $originalAccount,
                (float) $transaction->amount,
                $this->reverse($transaction->type),
            );

            $transaction->update($data);

            // Apply the new effect on the (possibly different) target account.
            $newAccount = Account::findOrFail($transaction->account_id);
            $this->accountService->applyDelta(
                $newAccount,
                (float) $transaction->amount,
                $transaction->type,
            );

            return $transaction;
        });
    }

    /**
     * Reverse the entry's balance effect, then delete it.
     */
    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $account = Account::findOrFail($transaction->account_id);
            $this->accountService->applyDelta(
                $account,
                (float) $transaction->amount,
                $this->reverse($transaction->type),
            );

            $transaction->delete();
        });
    }

    /**
     * The opposite ledger effect, used to undo a previously applied delta.
     */
    private function reverse(string $type): string
    {
        return $type === 'income' ? 'expense' : 'income';
    }
}
