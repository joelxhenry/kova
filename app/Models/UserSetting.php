<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public const DEFAULTS = [
        'business' => [
            'business_name' => '',
            'business_logo_path' => null,
            'business_address_line_1' => '',
            'business_address_line_2' => '',
            'business_city' => '',
            'business_state_or_parish' => '',
            'business_postal_code' => '',
            'business_country' => 'Jamaica',
            'business_phone' => '',
            'business_email' => '',
            'payment_terms' => 'Payment due within 14 days of invoice date.',
            'payment_instructions' => '',
        ],
        'invoicing' => [
            'invoice_prefix' => 'INV',
            'invoice_separator' => '-',
            'invoice_next_number' => 1,
            'invoice_padding' => 4,
        ],
        'email' => [
            'invoice_email_subject' => 'Invoice {invoice_number} from {business_name}',
            'invoice_email_greeting' => 'Hi {client_name},',
            'invoice_email_body' => 'Please find attached invoice {invoice_number} for {total}.',
            'invoice_email_footer' => 'Thank you for your business.',
            'invoice_email_include_payment_instructions' => true,
        ],
    ];

    /**
     * Get a setting value with fallback to default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        if (array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        // Search defaults
        foreach (self::DEFAULTS as $group) {
            if (array_key_exists($key, $group)) {
                return $group[$key];
            }
        }

        return $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Get all settings for a group, merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        $defaults = self::DEFAULTS[$group] ?? [];
        $settings = $this->settings ?? [];

        $result = [];
        foreach ($defaults as $key => $defaultValue) {
            $result[$key] = array_key_exists($key, $settings) ? $settings[$key] : $defaultValue;
        }

        return $result;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
