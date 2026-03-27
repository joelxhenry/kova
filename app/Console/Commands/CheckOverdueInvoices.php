<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdueNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'kova:check-overdue-invoices';
    protected $description = 'Mark past-due invoices as overdue and notify users';

    public function handle(): int
    {
        $overdue = Invoice::where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with('user')
            ->get();

        $count = 0;

        foreach ($overdue as $invoice) {
            $invoice->update(['status' => 'overdue']);

            $invoice->user->notify(new InvoiceOverdueNotification($invoice));

            $count++;
        }

        $this->info("Marked {$count} invoices as overdue.");

        return self::SUCCESS;
    }
}
