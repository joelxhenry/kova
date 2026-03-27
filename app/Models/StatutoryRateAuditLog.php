<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryRateAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'statutory_rate_audit_log';

    /** @var list<string> */
    protected $fillable = [
        'statutory_rate_id',
        'user_id',
        'old_value',
        'new_value',
        'old_effective_from',
        'new_effective_from',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_value' => 'decimal:4',
            'new_value' => 'decimal:4',
            'old_effective_from' => 'date',
            'new_effective_from' => 'date',
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StatutoryRate, $this>
     */
    public function statutoryRate(): BelongsTo
    {
        return $this->belongsTo(StatutoryRate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
