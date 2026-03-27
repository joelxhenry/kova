<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatutoryRate extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'key',
        'label',
        'value',
        'description',
        'effective_from',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'effective_from' => 'date',
        ];
    }

    /**
     * Get the statutory rate value effective at a given date.
     * Returns the most recent rate where effective_from <= $date.
     */
    public static function getValue(string $key, Carbon|string|null $date = null): float
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        return (float) static::where('key', $key)
            ->where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->value('value');
    }

    /**
     * Get the current (latest) record for a given key.
     */
    public static function current(string $key): ?self
    {
        return static::where('key', $key)
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @return HasMany<StatutoryRateAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(StatutoryRateAuditLog::class)->orderByDesc('changed_at');
    }
}
