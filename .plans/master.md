# Kova — Master Implementation Plan

**Product:** Contractor Income & Tax Management Tool (SaaS)
**Market:** Jamaica (TAJ compliance)
**Stack:** Laravel 13 · Vue 3 · Inertia.js · PrimeVue · Tailwind CSS
**Model:** Subscription-based (pricing TBD) with admin portal on separate subdomain

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
Statutory contribution rates (NIS, education tax, etc.) are **not** user-configurable — they are managed centrally via the admin portal and displayed read-only to users.

- [x] Migration: `tax_profiles` table
  ```
  user_id (FK), trn (Tax Registration Number), business_type enum (specified_services, construction, haulage, tillage, other),
  is_gct_registered (boolean, default false), gct_registration_date (nullable date),
  fiscal_year_start (date), created_at, updated_at
  ```
- [x] Migration: `statutory_rates` table (admin-managed, seeded with defaults)
  ```
  key (unique), label, value (decimal 15,4), description, effective_from (date)
  ```
  Seeded keys: `nis_rate`, `education_tax_rate`, `gct_rate`, `withholding_tax_rate`, `contractors_levy_rate`, `tax_free_threshold`, `tax_bracket_25_limit`, `gct_registration_threshold`, `withholding_tax_invoice_threshold`
- [x] Model: `TaxProfile` (belongsTo User), `StatutoryRate` (admin-managed lookup)
- [x] Service: `TaxProfileService` — create/update profile, validate TRN format
- [x] Form Request: `StoreTaxProfileRequest`
- [x] Controller: `TaxProfileController` (edit, update) — passes statutory rates as read-only props
- [x] Page: `Pages/Profile/TaxProfile.vue` — user edits their profile, statutory rates displayed read-only
- [x] The `business_type` determines which withholding logic applies (3% vs 2%)

### Verification

- [x] User can register, log in, and complete their tax profile
- [x] `business_type` selection persists and is accessible in session/shared Inertia props
- [x] Unauthenticated users are redirected to login

---

## Phase 1 — Income Tracking & Invoicing

Core data entry. Users log the money coming in.

### 1.1 Clients

- [x] Migration: `clients` table
  ```
  user_id (FK), name, email (nullable), phone (nullable),
  trn (nullable), is_designated_entity (boolean, default false),
  created_at, updated_at
  ```
- [x] Model: `Client` (belongsTo User, hasMany Invoices)
- [x] Service: `ClientService`
- [x] Form Requests: `StoreClientRequest`
- [x] Controller: `ClientController` (index, create, store, edit, update, destroy)
- [x] Pages: `Pages/Clients/Index.vue`, `Pages/Clients/Create.vue`, `Pages/Clients/Edit.vue`
- [x] `is_designated_entity` flag triggers withholding tax logic on invoices

### 1.2 Invoices

- [x] Migration: `invoices` table
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
- [x] Migration: `invoice_items` table
  ```
  invoice_id (FK), description, quantity (decimal), unit_price (decimal 15,2),
  amount (decimal 15,2), sort_order (int)
  ```
- [x] Models: `Invoice` (belongsTo User, belongsTo Client, hasMany InvoiceItems), `InvoiceItem`
- [x] Service: `InvoiceService`
  - Auto-generate sequential invoice numbers per user (INV-0001, INV-0002...)
  - Calculate subtotal from line items
  - If user is GCT-registered → apply GCT rate from `statutory_rates`
  - If client `is_designated_entity` && subtotal ≥ withholding threshold → apply withholding rate
  - If user `business_type` is construction/haulage/tillage → apply contractors levy rate
  - Compute `net_receivable = total - withholding_tax - contractors_levy`
- [x] Form Requests: `StoreInvoiceRequest` (validates client belongs to user)
- [x] Controller: `InvoiceController` (index with filters, create, store, show, edit, update, destroy)
- [x] Pages:
  - `Pages/Invoices/Index.vue` — list with filters (status, date range, client), pagination
  - `Pages/Invoices/Create.vue` — dynamic line items form with live subtotal
  - `Pages/Invoices/Show.vue` — invoice detail with itemized breakdown
  - `Pages/Invoices/Edit.vue` — edit with status change
- [x] Composable: `useCurrencyFormatter.js` — format JMD amounts consistently

### 1.3 Income Log (Non-Invoice Income)

For income that doesn't come from a formal invoice (e.g., cash jobs, ad-hoc payments).

- [x] Migration: `income_entries` table
  ```
  user_id (FK), source (string), description (text, nullable),
  amount (decimal 15,2), date_received (date),
  withholding_tax_applied (decimal 15,2, default 0),
  created_at, updated_at
  ```
- [x] Model: `IncomeEntry` (belongsTo User)
- [x] Service: `IncomeService`
- [x] Controller: `IncomeEntryController` (index, create, store, edit, update, destroy)
- [x] Pages: `Pages/Income/Index.vue`, `Pages/Income/Create.vue`, `Pages/Income/Edit.vue`

### Verification

- [x] User can create clients, marking some as designated entities
- [x] Invoices auto-calculate GCT, withholding tax, and contractors levy based on profile + client
- [x] Invoice list filters by status and date range
- [x] Non-invoice income can be logged separately
- [x] All amounts display as formatted JMD currency

---

## Phase 2 — Expense Management

Users offset gross revenue with business expenses to determine taxable income.

### 2.1 Expense Categories

- [x] Migration: `expense_categories` table
  ```
  user_id (FK, nullable — null = system default), name, description (nullable),
  is_default (boolean), sort_order (int)
  ```
- [x] Seeder: default categories (Equipment, Fuel & Transport, Office Rent, Software & Subscriptions, Professional Services, Utilities, Other)
- [x] Model: `ExpenseCategory` (hasMany Expenses, `forUser()` scope)

### 2.2 Expenses

- [x] Migration: `expenses` table
  ```
  user_id (FK), expense_category_id (FK), description (string),
  amount (decimal 15,2), date_incurred (date),
  receipt_path (string, nullable),
  notes (text, nullable),
  created_at, updated_at
  ```
- [x] Model: `Expense` (belongsTo User, belongsTo ExpenseCategory)
- [x] Service: `ExpenseService`
  - CRUD operations
  - Handle file upload for receipts (store in `storage/app/private/receipts/{user_id}/`)
  - Delete receipt file on expense deletion or receipt replacement
- [x] Form Requests: `StoreExpenseRequest`
  - Validate receipt file type (jpg, jpeg, png, pdf) and size (max 5MB)
  - Validate category belongs to user or is system default
- [x] Controller: `ExpenseController` (index with filters + totals, create, store, edit, update, destroy)
- [x] Pages:
  - `Pages/Expenses/Index.vue` — list with category filter, date range, totals by category summary
  - `Pages/Expenses/Create.vue` — form with receipt file upload
  - `Pages/Expenses/Edit.vue` — edit with receipt replacement

### Verification

- [x] User can log expenses with categories and receipt attachments
- [x] Receipts upload and are accessible only to the owning user
- [x] Expense totals aggregate correctly by category and date range
- [x] Deleting an expense removes the associated receipt file

---

## Phase 3 — Tax Calculation Engine

The core intelligence of the application. Everything feeds into this.

### 3.1 Tax Calculation Service

- [x] Service: `TaxCalculationService` — the central engine
  - **Inputs:** user's total gross income (invoices + income entries), total expenses, withholding tax credits, tax profile
  - **Outputs:** structured tax breakdown

  ```
  calculateAnnualTax(User $user, int $year): TaxBreakdown
  ```

  **Calculation steps:**
  1. Gross Income = sum of all paid invoices + income entries for the year
  2. Total Expenses = sum of all expenses for the year
  3. Net Income = Gross Income − Total Expenses
  4. Income Tax (thresholds from `statutory_rates` table):
     - First JMD `tax_free_threshold` → 0%
     - Up to JMD `tax_bracket_25_limit` → 25%
     - Above `tax_bracket_25_limit` → 30%
  5. NIS Contribution = Net Income × `nis_rate` (from `statutory_rates`)
  6. Education Tax = Net Income × `education_tax_rate` (from `statutory_rates`)
  7. Total Tax Liability = Income Tax + NIS + Education Tax
  8. Withholding Tax Credits = sum of all withholding tax from invoices + income entries + manual credits
  9. Net Tax Payable = Total Tax Liability − Withholding Tax Credits

- [x] DTO: `TaxBreakdown` — structured object with all computed values
- [x] DTO: `QuarterlyEstimate` — quarter, deadline, amountDue, isPast

### 3.2 Quarterly Estimates

- [x] Service method: `calculateQuarterlyEstimates(User $user, int $year): array`
  - Divide net tax payable into 4 equal quarterly payments
  - Map to statutory deadlines: March 15, June 15, September 15, December 15
  - Track which quarters have passed and which are upcoming
  - Factor in withholding credits already applied

### 3.3 Withholding Tax Ledger

- [x] Migration: `withholding_credits` table
  ```
  user_id (FK), source_type (invoice/manual), source_id (nullable),
  amount (decimal 15,2), tax_year (int), date_withheld (date),
  description (string), created_at, updated_at
  ```
- [x] Model: `WithholdingCredit`
- [x] Service: `WithholdingCreditService`
  - Auto-create entries when invoices are marked as paid (duplicate prevention)
  - Manual entries for non-invoice withholding
  - Aggregate credits by tax year
- [x] Controller: `WithholdingCreditController` (index with year filter + summary, store, destroy)
- [x] Page: `Pages/Tax/WithholdingCredits.vue` — ledger view with summary, year selector, manual credit form

### Verification

- [x] Tax engine accurately applies progressive brackets to test scenarios (16 unit tests)
- [x] Withholding credits deduct from total liability
- [x] Quarterly estimates split correctly across 4 deadlines
- [x] Withholding credits auto-created on invoice payment, no duplicates

---

## Phase 4 — Dashboard & Visualizations

The primary interface users see. Surfaces all the engine outputs.

### 4.1 Main Dashboard

- [x] Controller: `DashboardController` — aggregates data via TaxCalculationService + GctMonitorService + monthly breakdowns
- [x] Page: `Pages/Dashboard.vue` (replaced placeholder with full widget dashboard)
- [x] Components:
  - `Components/Domain/TaxSummaryCard.vue` — gross income, expenses, net income, net payable, income tax/NIS/education tax breakdown
  - `Components/Domain/QuarterlyEstimatesTimeline.vue` — 4 quarterly blocks with amount due, past/due status, deadline dates
  - `Components/Domain/IncomeVsExpenseChart.vue` — bar chart with monthly income vs expenses (12 months)
  - `Components/Domain/WithholdingCreditsWidget.vue` — total credits with link to ledger
  - `Components/Domain/GctThresholdTracker.vue` — progress bar toward threshold with percentage + warning at 80%+

### 4.2 GCT Threshold Alert System

- [x] Service: `GctMonitorService`
  - Calculate annual turnover from sent/paid invoices + income entries
  - Return percentage toward threshold (capped at 100%)
  - Reports isRegistered status from tax profile
- [ ] Notification: `GctThresholdApproachingNotification` (database + mail channel) — deferred to Phase 6
- [ ] Listener: check threshold after each invoice is created/paid — deferred to Phase 6

### 4.3 Year Selector

- [x] Composable: `useFiscalYear.js` — manage selected tax year, navigate with query param
- [x] All dashboard widgets filter by the selected year via DashboardController
- [x] Default to current year, allow switching to previous years (5-year range)

### Verification

- [x] Dashboard loads with real aggregated data from all modules
- [x] Quarterly timeline accurately reflects past/due quarters
- [x] GCT tracker shows correct percentage
- [x] Switching fiscal year updates all widgets

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

## Phase 8 — Admin Portal

Separate subdomain (`admin.kova.zncn.app`) for platform administration. Controls statutory rates, user management, and subscription oversight.

### 8.1 Admin Authentication & Authorization

- [ ] Admin `role` column on `users` table (or separate `admins` table)
- [ ] Admin auth middleware — separate guard or role-based check
- [ ] Admin login page on admin subdomain
- [ ] Route group with admin middleware, served under admin subdomain
- [ ] Separate Inertia entry point or route-based subdomain handling

### 8.2 Statutory Rate Management

- [ ] Controller: `Admin\StatutoryRateController` (index, edit, update)
- [ ] Form Request: `Admin\UpdateStatutoryRateRequest`
- [ ] Pages:
  - `Pages/Admin/StatutoryRates/Index.vue` — list all rates with current values
  - `Pages/Admin/StatutoryRates/Edit.vue` — update value and effective date
- [ ] Audit log: track who changed what rate and when (optional `statutory_rate_audit_log` table)
- [ ] Rate changes take effect for all users from `effective_from` date forward

### 8.3 User Management

- [ ] Controller: `Admin\UserController` (index, show, suspend, reactivate)
- [ ] Pages:
  - `Pages/Admin/Users/Index.vue` — paginated user list with search, subscription status
  - `Pages/Admin/Users/Show.vue` — user detail, tax profile, subscription info
- [ ] Ability to suspend/reactivate user accounts
- [ ] View user's subscription status and history

### 8.4 Platform Dashboard

- [ ] Controller: `Admin\DashboardController`
- [ ] Page: `Pages/Admin/Dashboard.vue`
- [ ] Widgets: total users, active subscriptions, revenue (when billing is live), recent signups

### Verification

- [ ] Admin can log in on admin subdomain
- [ ] Admin can update statutory rates and changes reflect for all users
- [ ] Admin can view and manage user accounts
- [ ] Regular users cannot access admin routes

---

## Phase 9 — Subscription & Billing

Subscription-based access model. Pricing structure and tiers are TBD — this phase defines the infrastructure.

### 9.1 Subscription Infrastructure

- [ ] Migration: `subscriptions` table
  ```
  user_id (FK), plan_id (FK), status enum (active, past_due, cancelled, trialing),
  trial_ends_at (nullable), current_period_start, current_period_end,
  cancelled_at (nullable), created_at, updated_at
  ```
- [ ] Migration: `plans` table (admin-managed)
  ```
  name, slug, description, price (decimal), interval enum (monthly, yearly),
  features (json), is_active (boolean), sort_order, created_at, updated_at
  ```
- [ ] Models: `Subscription`, `Plan`
- [ ] Service: `SubscriptionService` — manage subscription lifecycle
- [ ] Middleware: `EnsureSubscribed` — gate access to premium features
- [ ] Pricing page: display available plans (structure TBD)

### 9.2 Billing Integration (TBD)

- [ ] Payment gateway integration (provider TBD — Stripe, PayPal, local JM gateway)
- [ ] Webhook handling for payment events
- [ ] Invoice generation for subscription payments
- [ ] Billing history page for users

### 9.3 Admin Plan Management

- [ ] Controller: `Admin\PlanController` (index, create, store, edit, update)
- [ ] Pages: admin CRUD for plans — name, price, features, active toggle
- [ ] Subscription analytics in admin dashboard

### Verification

- [ ] Users can view available plans
- [ ] Subscription middleware gates access appropriately
- [ ] Admin can create/edit plans
- [ ] Subscription status visible in admin user management

---

## Data Model Summary

```
User
 ├── TaxProfile (1:1)
 ├── Subscription (1:1 active)
 │    └── Plan (N:1)
 ├── Client (1:N)
 │    └── Invoice (1:N)
 │         └── InvoiceItem (1:N)
 ├── IncomeEntry (1:N)
 ├── Expense (1:N)
 │    └── ExpenseCategory (N:1)
 ├── WithholdingCredit (1:N)
 └── TaxFormSnapshot (1:N)

StatutoryRate (admin-managed, global)
Plan (admin-managed, global)
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
   │
Phase 8 ─── Admin Portal (can start after Phase 0, independent of Phases 1-7)
   │
Phase 9 ─── Subscription & Billing (depends on Phase 8)
```

Phases 4 and 5 can run in parallel after Phase 3 is complete.
Phase 6 can begin as soon as Phase 4 is done.
Phase 8 (Admin Portal) can be developed in parallel with Phases 1-7.
Phase 9 (Subscriptions) requires Phase 8 for plan management.
