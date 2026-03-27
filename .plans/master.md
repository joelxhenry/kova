# Kova — Master Implementation Plan

**Product:** Contractor Income & Tax Management Tool
**Market:** Jamaica (TAJ compliance)
**Stack:** Laravel 13 · Vue 3 · Inertia.js · PrimeVue · Tailwind CSS

---

## Phase 0 — Authentication & User Profile

Foundation layer. Nothing else works without users and their tax context.

### 0.1 Authentication System

- [x] Install and configure Laravel Breeze (Inertia + Vue stack) or build auth manually
- [x] Pages: Login, Register, Forgot Password, Reset Password
- [x] Middleware: redirect unauthenticated users, guest-only gates for login/register
- [x] `AuthenticatedLayout.vue` — persistent sidebar/nav layout for logged-in users
- [x] Style all auth pages to the Bold Typography design system

### 0.2 Tax Profile (Onboarding)

After registration, the user must configure their tax identity. This drives all downstream calculations.

- [ ] Migration: `tax_profiles` table
  ```
  user_id (FK), trn (Tax Registration Number), business_type enum (specified_services, construction, haulage, tillage, other),
  is_gct_registered (boolean, default false), gct_registration_date (nullable date),
  nis_rate (decimal), education_tax_rate (decimal),
  fiscal_year_start (date), created_at, updated_at
  ```
- [ ] Model: `TaxProfile` (belongsTo User)
- [ ] Service: `TaxProfileService` — create/update profile, validate TRN format
- [ ] Form Request: `StoreTaxProfileRequest` / `UpdateTaxProfileRequest`
- [ ] Controller: `TaxProfileController` (edit, update)
- [ ] Page: `Pages/Profile/TaxProfile.vue` — onboarding wizard or settings page
- [ ] The `business_type` determines which withholding logic applies (3% vs 2%)

### Verification

- [ ] User can register, log in, and complete their tax profile
- [ ] `business_type` selection persists and is accessible in session/shared Inertia props
- [ ] Unauthenticated users are redirected to login

---

## Phase 1 — Income Tracking & Invoicing

Core data entry. Users log the money coming in.

### 1.1 Clients

- [ ] Migration: `clients` table
  ```
  user_id (FK), name, email (nullable), phone (nullable),
  trn (nullable), is_designated_entity (boolean, default false),
  created_at, updated_at
  ```
- [ ] Model: `Client` (belongsTo User, hasMany Invoices)
- [ ] Service: `ClientService`
- [ ] Form Requests: `StoreClientRequest` / `UpdateClientRequest`
- [ ] Controller: `ClientController` (index, create, store, edit, update, destroy)
- [ ] Pages: `Pages/Clients/Index.vue`, `Pages/Clients/Create.vue`, `Pages/Clients/Edit.vue`
- [ ] `is_designated_entity` flag triggers withholding tax logic on invoices

### 1.2 Invoices

- [ ] Migration: `invoices` table
  ```
  user_id (FK), client_id (FK), invoice_number (unique per user),
  issue_date, due_date (nullable),
  subtotal (decimal 15,2), gct_amount (decimal 15,2, default 0),
  total (decimal 15,2),
  withholding_tax_amount (decimal 15,2, default 0),
  contractors_levy_amount (decimal 15,2, default 0),
  net_receivable (decimal 15,2),
  status enum (draft, sent, paid, overdue, cancelled),
  notes (text, nullable),
  created_at, updated_at
  ```
- [ ] Migration: `invoice_items` table
  ```
  invoice_id (FK), description, quantity (decimal), unit_price (decimal 15,2),
  amount (decimal 15,2), sort_order (int)
  ```
- [ ] Models: `Invoice` (belongsTo User, belongsTo Client, hasMany InvoiceItems), `InvoiceItem`
- [ ] Service: `InvoiceService`
  - Auto-generate sequential invoice numbers per user
  - Calculate subtotal from line items
  - If user is GCT-registered → append 15% GCT line
  - If client `is_designated_entity` && subtotal > JMD $50,000 → calculate 3% withholding tax
  - If user `business_type` is construction/haulage/tillage → calculate 2% contractors levy
  - Compute `net_receivable = total - withholding_tax - contractors_levy`
- [ ] Form Requests: `StoreInvoiceRequest` / `UpdateInvoiceRequest`
- [ ] Controller: `InvoiceController` (index, create, store, show, edit, update, destroy)
- [ ] Pages:
  - `Pages/Invoices/Index.vue` — list with filters (status, date range, client)
  - `Pages/Invoices/Create.vue` — dynamic line items form
  - `Pages/Invoices/Show.vue` — invoice detail view
  - `Pages/Invoices/Edit.vue`
- [ ] Composable: `useCurrencyFormatter.js` — format JMD amounts consistently

### 1.3 Income Log (Non-Invoice Income)

For income that doesn't come from a formal invoice (e.g., cash jobs, ad-hoc payments).

- [ ] Migration: `income_entries` table
  ```
  user_id (FK), source (string), description (text, nullable),
  amount (decimal 15,2), date_received (date),
  withholding_tax_applied (decimal 15,2, default 0),
  created_at, updated_at
  ```
- [ ] Model: `IncomeEntry` (belongsTo User)
- [ ] Service: `IncomeService`
- [ ] Controller: `IncomeEntryController` (index, create, store, edit, update, destroy)
- [ ] Pages: `Pages/Income/Index.vue`, `Pages/Income/Create.vue`

### Verification

- [ ] User can create clients, marking some as designated entities
- [ ] Invoices auto-calculate GCT, withholding tax, and contractors levy based on profile + client
- [ ] Invoice list filters by status and date range
- [ ] Non-invoice income can be logged separately
- [ ] All amounts display as formatted JMD currency

---

## Phase 2 — Expense Management

Users offset gross revenue with business expenses to determine taxable income.

### 2.1 Expense Categories

- [ ] Migration: `expense_categories` table
  ```
  user_id (FK, nullable — null = system default), name, description (nullable),
  is_default (boolean), sort_order (int)
  ```
- [ ] Seeder: default categories (Equipment, Fuel & Transport, Office Rent, Software & Subscriptions, Professional Services, Utilities, Other)
- [ ] Model: `ExpenseCategory` (hasMany Expenses)

### 2.2 Expenses

- [ ] Migration: `expenses` table
  ```
  user_id (FK), expense_category_id (FK), description (string),
  amount (decimal 15,2), date_incurred (date),
  receipt_path (string, nullable),
  notes (text, nullable),
  created_at, updated_at
  ```
- [ ] Model: `Expense` (belongsTo User, belongsTo ExpenseCategory)
- [ ] Service: `ExpenseService`
  - CRUD operations
  - Handle file upload for receipts (store in `storage/app/private/receipts/{user_id}/`)
  - Aggregate expenses by category for a given period
- [ ] Form Requests: `StoreExpenseRequest` / `UpdateExpenseRequest`
  - Validate receipt file type (jpg, png, pdf) and size (max 5MB)
- [ ] Controller: `ExpenseController` (index, create, store, edit, update, destroy)
- [ ] Pages:
  - `Pages/Expenses/Index.vue` — list with category filter, date range, totals summary
  - `Pages/Expenses/Create.vue` — form with receipt upload dropzone
  - `Pages/Expenses/Edit.vue`

### Verification

- [ ] User can log expenses with categories and receipt attachments
- [ ] Receipts upload and are accessible only to the owning user
- [ ] Expense totals aggregate correctly by category and date range
- [ ] Deleting an expense removes the associated receipt file

---

## Phase 3 — Tax Calculation Engine

The core intelligence of the application. Everything feeds into this.

### 3.1 Tax Calculation Service

- [ ] Service: `TaxCalculationService` — the central engine
  - **Inputs:** user's total gross income (invoices + income entries), total expenses, withholding tax credits, tax profile
  - **Outputs:** structured tax breakdown

  ```
  calculateAnnualTax(User $user, int $year): TaxBreakdown
  ```

  **Calculation steps:**
  1. Gross Income = sum of all paid invoices + income entries for the year
  2. Total Expenses = sum of all expenses for the year
  3. Net Income = Gross Income − Total Expenses
  4. Income Tax:
     - First JMD $1,700,088 → 0% (tax-free threshold)
     - Next JMD $4,299,912 (up to $6,000,000) → 25%
     - Above JMD $6,000,000 → 30%
  5. NIS Contribution = Net Income × NIS rate (from tax profile)
  6. Education Tax = Net Income × Education Tax rate (from tax profile)
  7. Total Tax Liability = Income Tax + NIS + Education Tax
  8. Withholding Tax Credits = sum of all withholding tax from invoices + income entries
  9. Net Tax Payable = Total Tax Liability − Withholding Tax Credits

- [ ] DTO: `TaxBreakdown` — structured object with all computed values
  ```php
  grossIncome, totalExpenses, netIncome,
  taxFreeAmount, bracket25Amount, bracket25Tax,
  bracket30Amount, bracket30Tax, totalIncomeTax,
  nisContribution, educationTax,
  totalTaxLiability, withholdingCredits, netTaxPayable
  ```

### 3.2 Quarterly Estimates

- [ ] Service method: `calculateQuarterlyEstimates(User $user, int $year): array`
  - Divide net tax payable into 4 equal quarterly payments
  - Map to statutory deadlines: March 15, June 15, September 15, December 15
  - Track which quarters have passed and which are upcoming
  - Factor in withholding credits already applied

### 3.3 Withholding Tax Ledger

- [ ] Migration: `withholding_credits` table (if not derived purely from invoices)
  ```
  user_id (FK), source_type (invoice/manual), source_id (nullable),
  amount (decimal 15,2), tax_year (int), date_withheld (date),
  description (string), created_at, updated_at
  ```
- [ ] Model: `WithholdingCredit`
- [ ] Service: `WithholdingCreditService`
  - Auto-create entries when invoices are marked as paid
  - Allow manual entries for non-invoice withholding
  - Aggregate credits by tax year
- [ ] Controller: `WithholdingCreditController` (index, create, store, destroy)
- [ ] Page: `Pages/Tax/WithholdingCredits.vue` — ledger view

### Verification

- [ ] Tax engine accurately applies progressive brackets to test scenarios
- [ ] Withholding credits deduct from total liability
- [ ] Quarterly estimates split correctly across 4 deadlines
- [ ] Changing income/expenses instantly updates the tax calculation (no stale data)

---

## Phase 4 — Dashboard & Visualizations

The primary interface users see. Surfaces all the engine outputs.

### 4.1 Main Dashboard

- [ ] Controller: `DashboardController` — aggregates data via services
- [ ] Page: `Pages/Dashboard.vue` (replace current placeholder)
- [ ] Components:
  - `Components/Domain/TaxSummaryCard.vue` — net income, total liability, net payable at a glance
  - `Components/Domain/QuarterlyEstimatesTimeline.vue` — 4 quarterly blocks showing amount due, paid status, and upcoming deadline
  - `Components/Domain/IncomeVsExpenseChart.vue` — bar or line chart (monthly breakdown)
  - `Components/Domain/WithholdingCreditsWidget.vue` — total credits applied this year
  - `Components/Domain/GctThresholdTracker.vue` — progress bar toward JMD $15M threshold

### 4.2 GCT Threshold Alert System

- [ ] Service: `GctMonitorService`
  - Calculate annual turnover from invoices
  - Return percentage toward JMD $15,000,000 threshold
  - Trigger notification when user crosses 80%, 90%, 100%
- [ ] Notification: `GctThresholdApproachingNotification` (database + mail channel)
- [ ] Listener: check threshold after each invoice is created/paid

### 4.3 Year Selector

- [ ] Composable: `useFiscalYear.js` — manage selected tax year across dashboard
- [ ] All dashboard widgets filter by the selected year
- [ ] Default to current year, allow switching to previous years

### Verification

- [ ] Dashboard loads with real aggregated data from all modules
- [ ] Quarterly timeline accurately reflects paid/unpaid quarters
- [ ] GCT tracker shows correct percentage and triggers alerts at threshold
- [ ] Switching fiscal year updates all widgets

---

## Phase 5 — TAJ Form Generation

The final deliverable — bridging Kova data to official government forms.

### 5.1 Form S04 / IT01 Data Mapping

- [ ] Service: `TajFormService`
  - Aggregate all data for a given tax year into the S04/IT01 structure
  - Line item mapping:
    - Gross Professional/Business Income
    - Less: Allowable Expenses (by category)
    - Net Statutory Income
    - Less: Tax-Free Threshold
    - Tax on first bracket (25%)
    - Tax on remaining (30%)
    - Less: NIS
    - Less: Education Tax
    - Less: Withholding Tax Credits
    - Net Tax Payable / Refund Due
  - Return structured data ready for PDF rendering

### 5.2 PDF Generation

- [ ] Service: `PdfGenerationService`
  - Generate a clean, printable PDF that mirrors the TAJ form layout
  - Use Laravel's built-in PDF support or a blade-to-PDF approach
  - Include: user info (name, TRN), tax year, all computed line items
  - Store generated PDFs in `storage/app/private/tax-forms/{user_id}/`
- [ ] Controller: `TaxFormController` (show preview, download PDF)
- [ ] Pages:
  - `Pages/Tax/FormPreview.vue` — on-screen preview of the S04/IT01 data
  - Download button triggers PDF generation and streams the file

### 5.3 Tax Form History

- [ ] Migration: `tax_form_snapshots` table
  ```
  user_id (FK), tax_year (int), form_type (string),
  data (json — frozen snapshot of all computed values),
  pdf_path (string, nullable),
  generated_at (timestamp), created_at, updated_at
  ```
- [ ] Model: `TaxFormSnapshot`
- [ ] Users can regenerate forms, but previous snapshots are preserved for audit

### Verification

- [ ] Form preview renders all correct line items for a given tax year
- [ ] PDF downloads with accurate data matching the preview
- [ ] Multiple regenerations preserve snapshot history
- [ ] Generated values match manual calculation of the same test data

---

## Phase 6 — Notifications & Scheduled Tasks

Proactive reminders so users never miss a deadline.

### 6.1 Notification System

- [ ] Notifications (database + mail via Mailpit in dev):
  - `QuarterlyPaymentReminderNotification` — 14 days and 3 days before each deadline
  - `GctThresholdApproachingNotification` — at 80%, 90%, 100% of JMD $15M
  - `InvoiceOverdueNotification` — when an invoice passes its due date
- [ ] Page: `Pages/Notifications/Index.vue` — notification center (read/unread)
- [ ] Shared Inertia prop: unread notification count in nav

### 6.2 Scheduled Commands

- [ ] `app/Console/Commands/SendQuarterlyReminders.php`
  - Run daily, check if any user has a quarterly deadline within 14 or 3 days
  - Dispatch notification if not already sent for that quarter
- [ ] `app/Console/Commands/CheckOverdueInvoices.php`
  - Run daily, mark invoices past `due_date` as overdue, notify user
- [ ] `app/Console/Commands/CheckGctThreshold.php`
  - Run weekly, check each user's annual turnover against JMD $15M
- [ ] Register all commands in `routes/console.php` with `Schedule`

### Verification

- [ ] Quarterly reminders send at correct intervals (test with tinker)
- [ ] Overdue invoices auto-update status and trigger notification
- [ ] GCT threshold alerts fire at correct percentages
- [ ] Emails appear in Mailpit during development

---

## Phase 7 — Polish & Production Readiness

### 7.1 UI/UX Polish

- [ ] Apply Bold Typography design system comprehensively across all pages
- [ ] Responsive audit — ensure all pages work on mobile/tablet
- [ ] Empty states for all list pages (no invoices yet, no expenses, etc.)
- [ ] Loading states on all form submissions (Inertia progress bar)
- [ ] Toast notifications for success/error feedback
- [ ] Confirmation dialogs for destructive actions (delete invoice, expense)

### 7.2 Data Integrity

- [ ] Add database indexes on frequently queried columns (user_id, date fields, status)
- [ ] Add cascading deletes or soft deletes where appropriate
- [ ] Validate all monetary calculations with Pest tests against known TAJ examples
- [ ] Feature tests for every controller action
- [ ] Unit tests for `TaxCalculationService` with edge cases:
  - Income exactly at threshold boundaries
  - Zero income
  - Income exceeding JMD $6M bracket
  - Withholding credits exceeding tax liability (refund scenario)

### 7.3 Security Audit

- [ ] Ensure all routes are authorized (Policies or Form Request `authorize()`)
- [ ] Verify users can only access their own data (scoped queries)
- [ ] No sensitive data in Inertia props
- [ ] Rate limiting on auth routes
- [ ] CSRF protection on all forms (handled by Inertia)

### 7.4 Performance

- [ ] Eager loading audit — no N+1 queries
- [ ] Cache expensive tax calculations (invalidate on income/expense change)
- [ ] Paginate all list views

### Verification

- [ ] Full Pest test suite passes
- [ ] Lighthouse audit scores > 90 on performance and accessibility
- [ ] No N+1 queries (check with Laravel Debugbar or `DB::listen`)

---

## Data Model Summary

```
User
 ├── TaxProfile (1:1)
 ├── Client (1:N)
 │    └── Invoice (1:N)
 │         └── InvoiceItem (1:N)
 ├── IncomeEntry (1:N)
 ├── Expense (1:N)
 │    └── ExpenseCategory (N:1)
 ├── WithholdingCredit (1:N)
 └── TaxFormSnapshot (1:N)
```

---

## Implementation Order & Dependencies

```
Phase 0 ─── Authentication & Tax Profile
   │
Phase 1 ─── Income Tracking (Clients → Invoices → Income Entries)
   │
Phase 2 ─── Expense Management (Categories → Expenses)
   │
Phase 3 ─── Tax Calculation Engine (depends on Phases 1 + 2)
   │
Phase 4 ─── Dashboard & Visualizations (depends on Phase 3)
   │
Phase 5 ─── TAJ Form Generation (depends on Phase 3)
   │
Phase 6 ─── Notifications & Scheduled Tasks (depends on Phases 1 + 3 + 4)
   │
Phase 7 ─── Polish & Production Readiness (all phases)
```

Phases 4 and 5 can run in parallel after Phase 3 is complete.
Phase 6 can begin as soon as Phase 4 is done.
