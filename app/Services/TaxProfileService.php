<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaxProfile;
use App\Models\User;

class TaxProfileService
{
    /**
     * Create or update the tax profile for a user.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(User $user, array $data): TaxProfile
    {
        if (! $data['is_gct_registered']) {
            $data['gct_registration_date'] = null;
        }

        return TaxProfile::updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );
    }
}
