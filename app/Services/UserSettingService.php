<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserSettingService
{
    /**
     * Get or create the settings record for a user.
     */
    public function getOrCreate(User $user): UserSetting
    {
        return UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['settings' => []],
        );
    }

    /**
     * Update settings for a specific group.
     *
     * @param array<string, mixed> $data
     */
    public function updateGroup(User $user, string $group, array $data): UserSetting
    {
        $setting = $this->getOrCreate($user);
        $current = $setting->settings ?? [];
        $defaults = UserSetting::DEFAULTS[$group] ?? [];

        foreach ($defaults as $key => $defaultValue) {
            if (array_key_exists($key, $data)) {
                $current[$key] = $data[$key];
            }
        }

        $setting->settings = $current;
        $setting->save();

        return $setting;
    }

    /**
     * Handle logo upload.
     */
    public function uploadLogo(User $user, UploadedFile $logo): string
    {
        $setting = $this->getOrCreate($user);

        // Delete old logo
        $oldPath = $setting->get('business_logo_path');
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $logo->store("logos/{$user->id}", 'public');

        $current = $setting->settings ?? [];
        $current['business_logo_path'] = $path;
        $setting->settings = $current;
        $setting->save();

        return $path;
    }

    /**
     * Remove the logo.
     */
    public function removeLogo(User $user): void
    {
        $setting = $this->getOrCreate($user);
        $oldPath = $setting->get('business_logo_path');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $current = $setting->settings ?? [];
        $current['business_logo_path'] = null;
        $setting->settings = $current;
        $setting->save();
    }

    /**
     * Generate the next invoice number based on user settings.
     */
    public function generateInvoiceNumber(User $user): string
    {
        $setting = $this->getOrCreate($user);

        $prefix = $setting->get('invoice_prefix', 'INV');
        $separator = $setting->get('invoice_separator', '-');
        $nextNumber = (int) $setting->get('invoice_next_number', 1);
        $padding = (int) $setting->get('invoice_padding', 4);

        $number = str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);

        // Increment for next time
        $current = $setting->settings ?? [];
        $current['invoice_next_number'] = $nextNumber + 1;
        $setting->settings = $current;
        $setting->save();

        return $prefix . $separator . $number;
    }

    /**
     * Preview what the next invoice number will look like (without incrementing).
     */
    public function previewInvoiceNumber(User $user): string
    {
        $setting = $this->getOrCreate($user);

        $prefix = $setting->get('invoice_prefix', 'INV');
        $separator = $setting->get('invoice_separator', '-');
        $nextNumber = (int) $setting->get('invoice_next_number', 1);
        $padding = (int) $setting->get('invoice_padding', 4);

        $number = str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);

        return $prefix . $separator . $number;
    }
}
