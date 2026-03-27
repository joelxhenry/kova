<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TaxProfile;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\WithholdingCredit;
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

        // Tax Profile — GCT-registered IT consultant
        TaxProfile::create([
            'user_id' => $user->id,
            'trn' => '123456789',
            'business_type' => 'specified_services',
            'is_gct_registered' => true,
            'gct_registration_date' => '2023-06-01',
            'fiscal_year_start' => '2026-01-01',
        ]);

        // Business Settings
        UserSetting::create([
            'user_id' => $user->id,
            'settings' => [
                'business_name' => 'Brown Digital Consulting',
                'business_address_line_1' => '14 Trafalgar Road',
                'business_address_line_2' => 'Suite 3B',
                'business_city' => 'Kingston 10',
                'business_state_or_parish' => 'Kingston',
                'business_postal_code' => 'JMAKN10',
                'business_country' => 'Jamaica',
                'business_phone' => '876-555-7890',
                'business_email' => 'marcus@browndigital.jm',
                'payment_terms' => 'Payment due within 14 days of invoice date.',
                'payment_instructions' => "Bank: National Commercial Bank\nAccount: 301-555-8899\nBranch: New Kingston",
                'invoice_prefix' => 'BDC',
                'invoice_separator' => '-',
                'invoice_next_number' => 8,
                'invoice_padding' => 4,
            ],
        ]);

        // ── Clients ────────────────────────────────────────────────

        $govClient = Client::create([
            'user_id' => $user->id,
            'name' => 'Ministry of Technology',
            'email' => 'procurement@mot.gov.jm',
            'phone' => '876-555-1000',
            'trn' => '000111222',
            'is_designated_entity' => true,
            'address_line_1' => '2 St Lucia Avenue',
            'city' => 'Kingston 5',
            'state_or_parish' => 'Kingston',
            'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $govClient->id, 'first_name' => 'Sharon', 'last_name' => 'Williams', 'email' => 'sharon.w@mot.gov.jm', 'phone' => '876-555-1001']);
        ClientContact::create(['client_id' => $govClient->id, 'first_name' => 'David', 'last_name' => 'Clarke', 'email' => 'david.c@mot.gov.jm']);

        $agencyClient = Client::create([
            'user_id' => $user->id,
            'name' => 'Island Creative Agency',
            'email' => 'accounts@islandcreative.jm',
            'phone' => '876-555-2200',
            'trn' => '333444555',
            'is_designated_entity' => false,
            'address_line_1' => '8 Oxford Road',
            'address_line_2' => 'Floor 2',
            'city' => 'Kingston 5',
            'state_or_parish' => 'Kingston',
            'postal_code' => 'JMAKN05',
            'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $agencyClient->id, 'first_name' => 'Keisha', 'last_name' => 'Morgan', 'email' => 'keisha@islandcreative.jm', 'phone' => '876-555-2201']);

        $startupClient = Client::create([
            'user_id' => $user->id,
            'name' => 'FreshCart Ltd',
            'email' => 'finance@freshcart.jm',
            'phone' => '876-555-3300',
            'is_designated_entity' => false,
            'address_line_1' => '15 Hope Road',
            'city' => 'Kingston 6',
            'state_or_parish' => 'Kingston',
            'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $startupClient->id, 'first_name' => 'Andre', 'last_name' => 'Campbell', 'email' => 'andre@freshcart.jm']);
        ClientContact::create(['client_id' => $startupClient->id, 'first_name' => 'Lisa', 'last_name' => 'Henry', 'email' => 'lisa@freshcart.jm', 'phone' => '876-555-3301']);

        $hotelClient = Client::create([
            'user_id' => $user->id,
            'name' => 'Sunset Beach Resort',
            'email' => 'admin@sunsetbeach.jm',
            'phone' => '876-555-4400',
            'trn' => '666777888',
            'is_designated_entity' => true,
            'address_line_1' => '1 Gloucester Avenue',
            'city' => 'Montego Bay',
            'state_or_parish' => 'St James',
            'country' => 'Jamaica',
        ]);
        ClientContact::create(['client_id' => $hotelClient->id, 'first_name' => 'Michael', 'last_name' => 'Stewart', 'email' => 'michael@sunsetbeach.jm']);

        $freelanceClient = Client::create([
            'user_id' => $user->id,
            'name' => 'Patrice Dawkins',
            'email' => 'patrice.dawkins@gmail.com',
            'phone' => '876-555-5500',
            'is_designated_entity' => false,
            'city' => 'Mandeville',
            'state_or_parish' => 'Manchester',
            'country' => 'Jamaica',
        ]);

        // ── Invoices ───────────────────────────────────────────────

        // Invoice 1 — Gov, PAID, large, with withholding tax
        $inv1 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $govClient->id,
            'invoice_number' => 'BDC-0001',
            'issue_date' => '2026-01-15',
            'due_date' => '2026-01-29',
            'subtotal' => 450000,
            'gct_amount' => 67500,
            'total' => 517500,
            'withholding_tax_amount' => 13500,
            'contractors_levy_amount' => 0,
            'net_receivable' => 504000,
            'status' => 'paid',
            'notes' => 'Phase 1 — IT infrastructure audit and report.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'IT Infrastructure Audit', 'unit' => 'days', 'quantity' => 5, 'unit_price' => 50000, 'amount' => 250000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'Security Assessment Report', 'unit' => 'report', 'quantity' => 1, 'unit_price' => 120000, 'amount' => 120000, 'sort_order' => 1]);
        InvoiceItem::create(['invoice_id' => $inv1->id, 'description' => 'Recommendations Workshop', 'unit' => 'sessions', 'quantity' => 2, 'unit_price' => 40000, 'amount' => 80000, 'sort_order' => 2]);

        WithholdingCredit::create([
            'user_id' => $user->id,
            'source_type' => 'invoice',
            'source_id' => $inv1->id,
            'amount' => 13500,
            'tax_year' => 2026,
            'date_withheld' => '2026-01-29',
            'description' => 'WHT from BDC-0001',
        ]);

        // Invoice 2 — Agency, PAID
        $inv2 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $agencyClient->id,
            'invoice_number' => 'BDC-0002',
            'issue_date' => '2026-02-01',
            'due_date' => '2026-02-15',
            'subtotal' => 180000,
            'gct_amount' => 27000,
            'total' => 207000,
            'withholding_tax_amount' => 0,
            'contractors_levy_amount' => 0,
            'net_receivable' => 207000,
            'status' => 'paid',
            'notes' => 'Website redesign — final delivery.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'description' => 'Website Redesign', 'unit' => 'project', 'quantity' => 1, 'unit_price' => 120000, 'amount' => 120000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv2->id, 'description' => 'Responsive Optimization', 'unit' => 'hours', 'quantity' => 12, 'unit_price' => 5000, 'amount' => 60000, 'sort_order' => 1]);

        // Invoice 3 — Startup, SENT
        $inv3 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $startupClient->id,
            'invoice_number' => 'BDC-0003',
            'issue_date' => '2026-02-20',
            'due_date' => '2026-03-06',
            'subtotal' => 320000,
            'gct_amount' => 48000,
            'total' => 368000,
            'withholding_tax_amount' => 0,
            'contractors_levy_amount' => 0,
            'net_receivable' => 368000,
            'status' => 'sent',
        ]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'description' => 'Mobile App Development — Sprint 1', 'unit' => 'sprint', 'quantity' => 1, 'unit_price' => 200000, 'amount' => 200000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv3->id, 'description' => 'API Integration', 'unit' => 'hours', 'quantity' => 24, 'unit_price' => 5000, 'amount' => 120000, 'sort_order' => 1]);

        // Invoice 4 — Hotel, PAID, with withholding
        $inv4 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $hotelClient->id,
            'invoice_number' => 'BDC-0004',
            'issue_date' => '2026-03-01',
            'due_date' => '2026-03-15',
            'subtotal' => 275000,
            'gct_amount' => 41250,
            'total' => 316250,
            'withholding_tax_amount' => 8250,
            'contractors_levy_amount' => 0,
            'net_receivable' => 308000,
            'status' => 'paid',
        ]);
        InvoiceItem::create(['invoice_id' => $inv4->id, 'description' => 'Booking System Customization', 'unit' => 'hours', 'quantity' => 35, 'unit_price' => 5000, 'amount' => 175000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv4->id, 'description' => 'Staff Training', 'unit' => 'sessions', 'quantity' => 4, 'unit_price' => 25000, 'amount' => 100000, 'sort_order' => 1]);

        WithholdingCredit::create([
            'user_id' => $user->id,
            'source_type' => 'invoice',
            'source_id' => $inv4->id,
            'amount' => 8250,
            'tax_year' => 2026,
            'date_withheld' => '2026-03-15',
            'description' => 'WHT from BDC-0004',
        ]);

        // Invoice 5 — Startup, OVERDUE
        $inv5 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $startupClient->id,
            'invoice_number' => 'BDC-0005',
            'issue_date' => '2026-01-05',
            'due_date' => '2026-01-19',
            'subtotal' => 95000,
            'gct_amount' => 14250,
            'total' => 109250,
            'withholding_tax_amount' => 0,
            'contractors_levy_amount' => 0,
            'net_receivable' => 109250,
            'status' => 'overdue',
            'notes' => 'Follow up sent March 10.',
        ]);
        InvoiceItem::create(['invoice_id' => $inv5->id, 'description' => 'Server Migration & Setup', 'unit' => 'hours', 'quantity' => 19, 'unit_price' => 5000, 'amount' => 95000, 'sort_order' => 0]);

        // Invoice 6 — Freelance client, DRAFT
        $inv6 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $freelanceClient->id,
            'invoice_number' => 'BDC-0006',
            'issue_date' => '2026-03-25',
            'subtotal' => 40000,
            'gct_amount' => 6000,
            'total' => 46000,
            'withholding_tax_amount' => 0,
            'contractors_levy_amount' => 0,
            'net_receivable' => 46000,
            'status' => 'draft',
        ]);
        InvoiceItem::create(['invoice_id' => $inv6->id, 'description' => 'WordPress Site Setup', 'unit' => 'project', 'quantity' => 1, 'unit_price' => 25000, 'amount' => 25000, 'sort_order' => 0]);
        InvoiceItem::create(['invoice_id' => $inv6->id, 'description' => 'Content Migration', 'unit' => 'hours', 'quantity' => 3, 'unit_price' => 5000, 'amount' => 15000, 'sort_order' => 1]);

        // Invoice 7 — Gov, SENT (current quarter)
        $inv7 = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $govClient->id,
            'invoice_number' => 'BDC-0007',
            'issue_date' => '2026-03-20',
            'due_date' => '2026-04-03',
            'subtotal' => 350000,
            'gct_amount' => 52500,
            'total' => 402500,
            'withholding_tax_amount' => 10500,
            'contractors_levy_amount' => 0,
            'net_receivable' => 392000,
            'status' => 'sent',
        ]);
        InvoiceItem::create(['invoice_id' => $inv7->id, 'description' => 'Phase 2 — Network Upgrade Consulting', 'unit' => 'days', 'quantity' => 7, 'unit_price' => 50000, 'amount' => 350000, 'sort_order' => 0]);

        // Manual withholding credit
        WithholdingCredit::create([
            'user_id' => $user->id,
            'source_type' => 'manual',
            'amount' => 3600,
            'tax_year' => 2026,
            'date_withheld' => '2026-03-08',
            'description' => 'WHT withheld by Jamaica Tech Conference organizers',
        ]);
    }
}
