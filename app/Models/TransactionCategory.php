<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'kind',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Restrict to categories usable for a given transaction kind. A `both`
     * category is valid for either income or expense entries.
     *
     * @param Builder<TransactionCategory> $query
     * @return Builder<TransactionCategory>
     */
    public function scopeKind(Builder $query, string $kind): Builder
    {
        return $query->whereIn('kind', [$kind, 'both']);
    }

    /**
     * The categories available to a user: their own rows plus the shared
     * system defaults (`user_id = null`), ordered for display.
     *
     * @return Collection<int, self>
     */
    public static function forUser(int $userId): Collection
    {
        return static::query()
            ->where('user_id', $userId)
            ->orWhereNull('user_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
