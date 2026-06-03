<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\RecurringTransactionService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin User ─────────────────────────────────────────────
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@kova.test',
            'is_admin' => true,
        ]);

        // ── Demo User ──────────────────────────────────────────────
        $user = User::factory()->create([
            'name' => 'Marcus Brown',
            'email' => 'demo@kova.test',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'settings' => [
                'business_name' => 'Brown Digital Consulting',
                'business_address_line_1' => '14 Trafalgar Road',
                'business_address_line_2' => 'Suite 3B',
                'business_city' => 'Kingston 10',
                'business_state_or_parish' => 'Kingston',
                'business_country' => 'Jamaica',
                'business_phone' => '876-555-7890',
                'business_email' => 'marcus@browndigital.jm',
                'payment_terms' => 'Payment due within 14 days of invoice date.',
                'payment_instructions' => "Bank: National Commercial Bank\nAccount: 301-555-8899\nBranch: New Kingston",
                'invoice_prefix' => 'BDC',
                'invoice_separator' => '-',
                'invoice_next_number' => 6,
                'invoice_padding' => 4,
            ],
        ]);

        // ── Clients ────────────────────────────────────────────────

        $govClient = Client::create([
            'user_id' => $user->id, 'name' => 'Ministry of Technology', 'email' => 'procurement@mot.gov.jm',
            'phone' => '876-555-1000', 'address_line_1' => '2 St Lucia Avenue',
            'city' => 'Kingston 5', 'state_or_parish' => 'Kingston', 'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $govClient->id, 'first_name' => 'Sharon', 'last_name' => 'Williams', 'email' => 'sharon.w@mot.gov.jm']);

        $agencyClient = Client::create([
            'user_id' => $user->id, 'name' => 'Island Creative Agency', 'email' => 'accounts@islandcreative.jm',
            'phone' => '876-555-2200', 'address_line_1' => '8 Oxford Road',
            'city' => 'Kingston 5', 'state_or_parish' => 'Kingston', 'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $agencyClient->id, 'first_name' => 'Keisha', 'last_name' => 'Morgan', 'email' => 'keisha@islandcreative.jm']);

        $startupClient = Client::create([
            'user_id' => $user->id, 'name' => 'FreshCart Ltd', 'email' => 'finance@freshcart.jm',
            'phone' => '876-555-3300', 'address_line_1' => '15 Hope Road',
            'city' => 'Kingston 6', 'state_or_parish' => 'Kingston', 'country' => 'Jamaica',
        ]);

        // ── Invoices ───────────────────────────────────────────────

        $inv1 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $govClient->id,
            'invoice_number' => 'BDC-0001', 'issue_date' => '2026-01-15', 'due_date' => '2026-01-29',
            'subtotal' => 450000, 'total' => 450000, 'status' => 'paid',
            'notes' => 'IT infrastructure audit and report.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'IT Infrastructure Audit', 'unit' => 'days', 'quantity' => 5, 'unit_price' => 50000, 'amount' => 250000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'Security Assessment Report', 'unit' => 'report', 'quantity' => 1, 'unit_price' => 120000, 'amount' => 120000, 'sort_order' => 1]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'Recommendations Workshop', 'unit' => 'sessions', 'quantity' => 2, 'unit_price' => 40000, 'amount' => 80000, 'sort_order' => 2]);

        $inv2 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $agencyClient->id,
            'invoice_number' => 'BDC-0002', 'issue_date' => '2026-02-01', 'due_date' => '2026-02-15',
            'subtotal' => 180000, 'total' => 180000, 'status' => 'paid',
            'notes' => 'Website redesign — final delivery.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'description' => 'Website Redesign', 'unit' => 'project', 'quantity' => 1, 'unit_price' => 120000, 'amount' => 120000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'description' => 'Responsive Optimization', 'unit' => 'hours', 'quantity' => 12, 'unit_price' => 5000, 'amount' => 60000, 'sort_order' => 1]);

        $inv3 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $startupClient->id,
            'invoice_number' => 'BDC-0003', 'issue_date' => '2026-02-20', 'due_date' => '2026-03-06',
            'subtotal' => 320000, 'total' => 320000, 'status' => 'sent',
        ]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'description' => 'Mobile App Development — Sprint 1', 'unit' => 'sprint', 'quantity' => 1, 'unit_price' => 200000, 'amount' => 200000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'description' => 'API Integration', 'unit' => 'hours', 'quantity' => 24, 'unit_price' => 5000, 'amount' => 120000, 'sort_order' => 1]);

        $inv4 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $startupClient->id,
            'invoice_number' => 'BDC-0004', 'issue_date' => '2026-01-05', 'due_date' => '2026-01-19',
            'subtotal' => 95000, 'total' => 95000, 'status' => 'overdue',
            'notes' => 'Follow up sent March 10.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv4->id, 'description' => 'Server Migration & Setup', 'unit' => 'hours', 'quantity' => 19, 'unit_price' => 5000, 'amount' => 95000, 'sort_order' => 0]);

        $inv5 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $govClient->id,
            'invoice_number' => 'BDC-0005', 'issue_date' => '2026-03-25',
            'subtotal' => 40000, 'total' => 40000, 'status' => 'draft',
        ]);
        InvoiceItem::create(['invoice_id' => $inv5->id, 'description' => 'WordPress Site Setup', 'unit' => 'project', 'quantity' => 1, 'unit_price' => 25000, 'amount' => 25000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv5->id, 'description' => 'Content Migration', 'unit' => 'hours', 'quantity' => 3, 'unit_price' => 5000, 'amount' => 15000, 'sort_order' => 1]);

        // ── Expenses ───────────────────────────────────────────────

        $categories = ExpenseCategory::whereNull('user_id')->get()->keyBy('name');

        $softwareCat = $categories['Software & Subscriptions'];
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $softwareCat->id, 'description' => 'DigitalOcean Hosting — Jan', 'amount' => 8500, 'date_incurred' => '2026-01-05']);
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $softwareCat->id, 'description' => 'DigitalOcean Hosting — Feb', 'amount' => 8500, 'date_incurred' => '2026-02-05']);
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $softwareCat->id, 'description' => 'Figma Pro Annual', 'amount' => 18000, 'date_incurred' => '2026-01-12']);

        $equipCat = $categories['Equipment'];
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $equipCat->id, 'description' => 'Dell 27" Monitor', 'amount' => 65000, 'date_incurred' => '2026-01-18']);

        $fuelCat = $categories['Fuel & Transport'];
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $fuelCat->id, 'description' => 'Fuel — client site visits Jan', 'amount' => 12000, 'date_incurred' => '2026-01-22']);
        Expense::create(['user_id' => $user->id, 'expense_category_id' => $fuelCat->id, 'description' => 'Fuel — client site visits Feb', 'amount' => 9500, 'date_incurred' => '2026-02-18']);

        // ── Budget: Accounts, Transactions, Recurring ──────────────
        // Routed through the services so cached balances stay consistent.
        $accountService = app(AccountService::class);
        $transactionService = app(TransactionService::class);
        $recurringService = app(RecurringTransactionService::class);

        $checking = $accountService->create($user, ['name' => 'NCB Checking', 'type' => 'debit', 'opening_balance' => 250000, 'is_active' => true, 'sort_order' => 0]);
        $savings = $accountService->create($user, ['name' => 'Scotia Savings', 'type' => 'debit', 'opening_balance' => 600000, 'is_active' => true, 'sort_order' => 1]);
        $card = $accountService->create($user, ['name' => 'Scotiabank Credit Card', 'type' => 'credit', 'opening_balance' => 48000, 'is_active' => true, 'sort_order' => 2]);

        $txCats = TransactionCategory::whereNull('user_id')->get()->keyBy('name');

        // One-off ledger entries (each updates the relevant cached balance).
        $transactionService->create($user, ['account_id' => $checking->id, 'type' => 'income', 'amount' => 180000, 'date' => '2026-05-02', 'description' => 'Invoice BDC-0002 payment', 'transaction_category_id' => $txCats['Freelance']->id]);
        $transactionService->create($user, ['account_id' => $checking->id, 'type' => 'expense', 'amount' => 28500, 'date' => '2026-05-06', 'description' => 'Supermarket run', 'transaction_category_id' => $txCats['Groceries']->id]);
        $transactionService->create($user, ['account_id' => $card->id, 'type' => 'expense', 'amount' => 15600, 'date' => '2026-05-09', 'description' => 'Restaurant dinner', 'transaction_category_id' => $txCats['Entertainment']->id]);
        $transactionService->create($user, ['account_id' => $card->id, 'type' => 'income', 'amount' => 30000, 'date' => '2026-05-20', 'description' => 'Credit card payment', 'transaction_category_id' => $txCats['Other Income']->id]);

        // Recurring rules (first run scheduled on start_date).
        $recurringService->create($user, ['account_id' => $checking->id, 'type' => 'expense', 'amount' => 85000, 'frequency' => 'monthly', 'start_date' => '2026-06-01', 'description' => 'Apartment rent', 'transaction_category_id' => $txCats['Rent']->id]);
        $recurringService->create($user, ['account_id' => $checking->id, 'type' => 'income', 'amount' => 220000, 'frequency' => 'monthly', 'start_date' => '2026-06-28', 'description' => 'Retainer — Island Creative', 'transaction_category_id' => $txCats['Freelance']->id]);
        $recurringService->create($user, ['account_id' => $checking->id, 'transfer_account_id' => $savings->id, 'type' => 'transfer', 'amount' => 50000, 'frequency' => 'monthly', 'start_date' => '2026-06-05', 'description' => 'Auto-save to savings']);
    }
}
