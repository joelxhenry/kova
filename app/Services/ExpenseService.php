<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data, ?UploadedFile $receipt = null): Expense
    {
        if ($receipt) {
            $data['receipt_path'] = $receipt->store("receipts/{$user->id}", 'private');
        }

        return $user->expenses()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Expense $expense, array $data, ?UploadedFile $receipt = null): Expense
    {
        if ($receipt) {
            if ($expense->receipt_path) {
                Storage::disk('private')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $receipt->store("receipts/{$expense->user_id}", 'private');
        }

        $expense->update($data);

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        if ($expense->receipt_path) {
            Storage::disk('private')->delete($expense->receipt_path);
        }

        $expense->delete();
    }
}
