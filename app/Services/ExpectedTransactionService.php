<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExpectedTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExpectedTransactionService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    /**
     * Persist an anticipated cash flow. An expected item is purely a forecast
     * input — it NEVER mutates an account balance until it is realized (FR-5.2).
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): ExpectedTransaction
    {
        $data['status'] = 'pending';

        return $user->expectedTransactions()->create($data);
    }

    /**
     * Update an anticipated cash flow. Plain persistence — no balance effect.
     *
     * @param array<string, mixed> $data
     */
    public function update(ExpectedTransaction $expected, array $data): ExpectedTransaction
    {
        $expected->update($data);

        return $expected;
    }

    /**
     * Delete an anticipated cash flow. No balance effect (it never posted one).
     */
    public function delete(ExpectedTransaction $expected): void
    {
        $expected->delete();
    }

    /**
     * Realize a pending expected item into a real ledger transaction (FR-5.3).
     *
     * The created `Transaction` (posted through TransactionService) is the single
     * source of the balance effect; the expected item is then stamped
     * `status=realized` with a provenance link. A realize-time override may swap
     * the account, date, or amount. One-way transition: only pending items with a
     * resolvable account may be realized.
     *
     * @param array<string, mixed> $overrides account_id|date|amount
     */
    public function realize(ExpectedTransaction $expected, array $overrides = []): Transaction
    {
        if ($expected->status !== 'pending') {
            throw new RuntimeException('Only a pending expected item can be realized.');
        }

        $accountId = $overrides['account_id'] ?? $expected->account_id;

        if ($accountId === null) {
            throw new RuntimeException('An account is required to realize an expected item.');
        }

        return DB::transaction(function () use ($expected, $overrides, $accountId): Transaction {
            $transaction = $this->transactionService->create($expected->user, [
                'account_id' => (int) $accountId,
                'transaction_category_id' => $expected->transaction_category_id,
                'type' => $expected->type,
                'amount' => $overrides['amount'] ?? $expected->amount,
                'date' => $overrides['date'] ?? $expected->expected_date->toDateString(),
                'description' => $expected->description,
                'notes' => $expected->notes,
            ]);

            $expected->update([
                'status' => 'realized',
                'realized_transaction_id' => $transaction->id,
                // Reflect the account the item actually landed on.
                'account_id' => (int) $accountId,
            ]);

            return $transaction;
        });
    }

    /**
     * Cancel a pending expected item: kept for history, excluded from the
     * projection. Cancelling never touches a balance.
     */
    public function cancel(ExpectedTransaction $expected): void
    {
        $expected->update(['status' => 'cancelled']);
    }
}
