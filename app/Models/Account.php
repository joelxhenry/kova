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
        'rate_basis',
        'credit_limit',
        'is_active',
        'sort_order',
    ];

    /** @var list<string> */
    protected $appends = [
        'available_credit',
        'effective_annual_rate',
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
     * The rate normalised to an Effective Annual Rate (%) so accounts quoted
     * as APR and as EAR can be compared on equal footing. Null when no rate.
     *
     * APR is nominal (compounded monthly): EAR = (1 + APR/12)^12 − 1.
     * An 'effective' rate is already an EAR and passes through unchanged.
     *
     * @return Attribute<float|null, never>
     */
    protected function effectiveAnnualRate(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->interest_rate === null) {
                    return null;
                }

                $rate = (float) $this->interest_rate / 100;

                $ear = $this->rate_basis === 'effective'
                    ? $rate
                    : (1 + $rate / 12) ** 12 - 1;

                return round($ear * 100, 3);
            },
        );
    }

    /**
     * The periodic (monthly) interest rate as a fraction, respecting how the
     * rate is quoted. Null when no rate is set. Shared by the monthly-interest
     * estimate and the projection engine so both compound consistently.
     *
     *  - APR:       monthly rate = APR ÷ 12.
     *  - Effective: monthly rate = (1 + EAR)^(1/12) − 1 (un-compounded), so a
     *               year of these monthly charges compounds back up to the EAR.
     *
     * @return Attribute<float|null, never>
     */
    protected function monthlyInterestRate(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->interest_rate === null) {
                    return null;
                }

                $rate = (float) $this->interest_rate / 100;

                return $this->rate_basis === 'effective'
                    ? (1 + $rate) ** (1 / 12) - 1
                    : $rate / 12;
            },
        );
    }

    /**
     * Interest accrued over one month at the current balance. Null when no rate.
     *
     * @return Attribute<float|null, never>
     */
    protected function estimatedMonthlyInterest(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->monthly_interest_rate === null) {
                    return null;
                }

                return round((float) $this->current_balance * $this->monthly_interest_rate, 2);
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
