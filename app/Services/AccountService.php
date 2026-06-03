<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountService
{
    /**
     * Net-worth summary from a set of accounts (debit assets − credit liabilities, FR-1.4).
     *
     * @param Collection<int, Account> $accounts
     * @return array{debit_total: float, credit_total: float, net_worth: float}
     */
    public function summary(Collection $accounts): array
    {
        $debitTotal = (float) $accounts->where('type', 'debit')->sum('current_balance');
        $creditTotal = (float) $accounts->where('type', 'credit')->sum('current_balance');

        return [
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'net_worth' => $debitTotal - $creditTotal,
        ];
    }

    /**
     * Create an account, seeding the cached balance from the opening balance.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Account
    {
        $data['current_balance'] = $data['opening_balance'] ?? 0;

        return $user->accounts()->create($data);
    }

    /**
     * Update an account. When the opening balance changes, the cached
     * current balance is re-derived as (new opening + accumulated ledger deltas)
     * so prior transaction effects are preserved.
     *
     * @param array<string, mixed> $data
     */
    public function update(Account $account, array $data): Account
    {
        if (array_key_exists('opening_balance', $data)) {
            $delta = (float) $data['opening_balance'] - (float) $account->opening_balance;
            $data['current_balance'] = (float) $account->current_balance + $delta;
        }

        $account->update($data);

        return $account;
    }

    /**
     * Delete an account. Blocks deletion while ledger transactions reference it
     * so cached balances elsewhere (e.g. transfer counterparts) cannot drift.
     */
    public function delete(Account $account): void
    {
        if ($account->transactions()->exists()) {
            throw new RuntimeException('Cannot delete an account that has transactions.');
        }

        $account->delete();
    }

    /**
     * Move money between two accounts as a single `type=transfer` ledger row.
     * The source leg behaves like an expense, the destination like income, so
     * the per-type sign rules apply to each account independently. A transfer is
     * never counted as income or expense in analytics.
     *
     * @param array<string, mixed> $data
     */
    public function transfer(Account $from, Account $to, array $data): Transaction
    {
        return DB::transaction(function () use ($from, $to, $data): Transaction {
            $amount = (float) $data['amount'];

            $transaction = Transaction::create([
                'user_id' => $from->user_id,
                'account_id' => $from->id,
                'transfer_account_id' => $to->id,
                'type' => 'transfer',
                'amount' => $amount,
                'date' => $data['date'],
                'description' => $data['description'] ?? 'Transfer',
            ]);

            $this->applyDelta($from, $amount, 'expense');
            $this->applyDelta($to, $amount, 'income');

            return $transaction;
        });
    }

    /**
     * Central balance-mutation helper (reused by TransactionService).
     *
     * Sign rules (FR-1.4):
     *  - Debit account:  income +, expense −.
     *  - Credit account: expense/charge +, income/payment −.
     *
     * @param string $type income|expense
     */
    public function applyDelta(Account $account, float $amount, string $type): void
    {
        $sign = ($type === 'income' ? 1 : -1) * ($account->type === 'credit' ? -1 : 1);

        $account->current_balance = (float) $account->current_balance + ($sign * $amount);
        $account->save();
    }
}
