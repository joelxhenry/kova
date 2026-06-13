<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'opening_balance',
        'current_balance',
        'interest_rate',
        'credit_limit',
        'is_active',
        'sort_order',
    ];

    /** @var list<string> */
    protected $appends = [
        'available_credit',
        'estimated_monthly_interest',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'interest_rate' => 'decimal:3',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Remaining headroom on a credit account (limit − outstanding balance).
     * Null for debit accounts or when no limit is set.
     *
     * @return Attribute<float|null, never>
     */
    protected function availableCredit(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->type !== 'credit' || $this->credit_limit === null) {
                    return null;
                }

                return round((float) $this->credit_limit - (float) $this->current_balance, 2);
            },
        );
    }

    /**
     * Interest accrued over one month at the current balance and APR
     * (annual rate ÷ 12). Null when no rate is set.
     *
     * @return Attribute<float|null, never>
     */
    protected function estimatedMonthlyInterest(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->interest_rate === null) {
                    return null;
                }

                return round((float) $this->current_balance * ((float) $this->interest_rate / 100) / 12, 2);
            },
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<RecurringTransaction, $this>
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * @param Builder<Account> $query
     * @return Builder<Account>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
