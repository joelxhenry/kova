<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\ExpectedTransactionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'account_id',
        'transaction_category_id',
        'type',
        'amount',
        'expected_date',
        'description',
        'notes',
        'status',
        'realized_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<TransactionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    /**
     * Provenance link to the ledger row created once this item is realized.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function realizedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'realized_transaction_id');
    }

    /**
     * @param Builder<ExpectedTransaction> $query
     * @return Builder<ExpectedTransaction>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
