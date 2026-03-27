<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'gct_amount',
        'total',
        'withholding_tax_amount',
        'contractors_levy_amount',
        'net_receivable',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'gct_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'withholding_tax_amount' => 'decimal:2',
            'contractors_levy_amount' => 'decimal:2',
            'net_receivable' => 'decimal:2',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }
}
