<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IncomeEntry;
use App\Models\User;

class IncomeService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): IncomeEntry
    {
        return $user->incomeEntries()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(IncomeEntry $entry, array $data): IncomeEntry
    {
        $entry->update($data);

        return $entry->fresh();
    }

    public function delete(IncomeEntry $entry): void
    {
        $entry->delete();
    }
}
