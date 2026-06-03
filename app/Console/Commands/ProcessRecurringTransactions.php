<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'kova:process-recurring';
    protected $description = 'Generate ledger transactions for all due recurring rules';

    public function handle(RecurringTransactionService $service): int
    {
        $count = $service->generateDue(Carbon::now());

        $this->info("Generated {$count} recurring transactions.");

        return self::SUCCESS;
    }
}
