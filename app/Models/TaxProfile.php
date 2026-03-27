<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'trn',
        'business_type',
        'is_gct_registered',
        'gct_registration_date',
        'nis_rate',
        'education_tax_rate',
        'fiscal_year_start',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_gct_registered' => 'boolean',
            'gct_registration_date' => 'date',
            'nis_rate' => 'decimal:2',
            'education_tax_rate' => 'decimal:2',
            'fiscal_year_start' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
