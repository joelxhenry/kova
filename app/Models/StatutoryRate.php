<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
     * Get a statutory rate value by key.
     */
    public static function getValue(string $key): float
    {
        return (float) static::where('key', $key)->value('value');
    }
}
