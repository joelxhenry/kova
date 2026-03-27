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

### 0.3 System Settings

User-configurable settings stored as a JSON column per user, organized by group.

**Migration & Model:**
- [x] Migration: `user_settings` table (`user_id FK unique, settings json`)
- [x] Model: `UserSetting` — `get(key, default)`, `set(key, value)`, `getGroup(group)` with `DEFAULTS` constant
- [x] User relationship: `hasOne UserSetting`

**Setting Groups:**

1. **Business Profile** (`business`)
   - [x] `business_name`, `business_logo_path` (stored in `storage/app/public/logos/{user_id}/`)
   - [x] `business_address_*` — line 1, line 2, city, state/parish, postal code, country (default Jamaica)
   - [x] `business_phone`, `business_email`
   - [x] `payment_terms`, `payment_instructions`

2. **Invoice Settings** (`invoicing`)
   - [x] `invoice_prefix` (default "INV"), `invoice_separator` (default "-")
   - [x] `invoice_next_number` (auto-incrementing), `invoice_padding` (default 4)
   - [x] Live preview of next invoice number in settings page

3. **Email Templates** (`email`)
   - [x] `invoice_email_subject`, `invoice_email_greeting`, `invoice_email_body`, `invoice_email_footer`
   - [x] Variables: `{invoice_number}`, `{business_name}`, `{client_name}`, `{total}`
   - [x] `invoice_email_include_payment_instructions` toggle

**Controller & Pages:**
- [x] Service: `UserSettingService` — get/set with defaults, logo upload/remove, `generateInvoiceNumber()`, `previewInvoiceNumber()`
- [x] Controller: `SettingsController` (index, updateBusiness, updateInvoicing, updateEmail, removeLogo)
- [x] Form Requests: `UpdateBusinessSettingsRequest`, `UpdateInvoiceSettingsRequest`, `UpdateEmailSettingsRequest`
- [x] Page: `Pages/Settings/Index.vue` — tabbed layout (Business Profile, Invoice Settings, Email Templates)
- [x] Routes: `GET /settings`, `PUT /settings/{group}`, `DELETE /settings/logo`
- [x] Shared Inertia props: `settings.business_name`, `settings.business_logo_path`

**Verification:**
- [x] Business profile settings persist (12 tests)
- [x] Invoice numbering generates and increments correctly
- [x] Logo uploads, displays, and removes
- [x] Default values work when no settings configured
- [x] Settings shared in Inertia props for nav/layouts

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

#### 1.1.1 Client Enhancements

- [x] Migration: add address columns to `clients` table (international-friendly)
  ```
  address_line_1, address_line_2, city, state_or_parish, postal_code, country (default 'Jamaica')
  ```
- [x] Migration: `client_contacts` table (`client_id FK cascadeOnDelete, first_name, last_name, email, phone`)
- [x] Model: `ClientContact` (belongsTo Client)
- [x] Updated `Client` model: `hasMany contacts`, `formattedAddress` accessor, address fields in `$fillable`
- [x] Updated `StoreClientRequest`: validates address fields + nested `contacts.*` array with first/last name required
- [x] Updated `ClientService`: `syncContacts()` — keeps existing by id, creates new, deletes removed
- [x] Updated `ClientController`: added `show` action with contacts, invoices, and financial summary
- [x] Page: `Pages/Clients/Show.vue` — client detail with:
  - Client info header (name, email, phone, address, TRN, designated badge)
  - Financial summary cards (total invoiced, total paid, balance due)
  - Contacts grid with name/email/phone per contact
  - Invoice list linked to invoice detail pages
- [x] Updated `Pages/Clients/Create.vue` and `Edit.vue`:
  - Address section (line 1, line 2, city, state/parish, postal code, country)
  - Dynamic contacts section (add/remove with first name, last name, email, phone per contact)
- [x] Updated route: full resource (removed `except(['show'])`)
- [x] Updated `Clients/Index.vue`: client names link to show page
- [x] Tests (12 tests):
  - Show page renders with invoices, summary, and contacts
  - Financial summary totals accurate (invoiced, paid, balance)
  - Contacts CRUD (create with client, sync on update, delete cascade)
  - Address fields persist
  - Contact validation (first/last name required)
  - Ownership auth on show + edit
  - Creating without contacts works

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

#### 1.2.1 Invoice Enhancements

**Invoice Items — Unit Field:**
- [x] Migration: add `unit` column (string, nullable) to `invoice_items` table
  - Stores what the quantity represents: "hours", "items", "days", "units", "sessions", etc.
- [x] Update `InvoiceItem` model, `StoreInvoiceRequest`, and `InvoiceService` to handle the `unit` field
- [x] Update Create/Edit forms to include a unit field per line item (text input)
- [x] Display unit in Show page line items table

**Invoice Number Configuration:**
- [x] Invoice numbering is configurable per user via Settings (see Phase 0.3)
  - Prefix (default "INV"), separator (default "-"), next number, zero-padding width
  - Example formats: `INV-0001`, `KV/2025/001`, `001`
- [x] Update `InvoiceService` to delegate to `UserSettingService.generateInvoiceNumber()`
- [x] Settings page shows current format preview and next number

**Invoice View Page — Professional Layout:**
- [x] Redesign `Pages/Invoices/Show.vue` to look like an actual printed invoice:
  - **Header**: user's business name (from settings) + invoice number + status badge
  - **Bill To section**: client name + address + TRN + contacts
  - **Invoice details**: issue date, due date, payment terms (right-aligned)
  - **Line items table**: description, unit, quantity, unit price, amount — with column headers
  - **Totals section**: subtotal, GCT, total, withholding tax, contractors levy, net receivable
  - **Notes/terms footer**: invoice notes + configurable payment instructions (from settings)
  - **Print styles**: `@media print` CSS hides nav/actions, clean layout
- [x] Quick action buttons:
  - **Update Status**: Select dropdown to change status (draft/sent/paid/overdue/cancelled)
  - **Send by Email**: opens recipient selection dialog (client email, contact emails, custom email input), sends email with PDF attachment
    - Auto-updates status to "sent" if currently "draft"
  - **Download PDF**: generates and downloads A4 PDF via `barryvdh/laravel-dompdf`
  - **Print**: browser print with print-optimized styles
  - **Duplicate**: creates new draft invoice with same line items and client
  - **Edit / Delete**: existing actions preserved

**Invoice Email System:**
- [x] Service: `InvoiceEmailService`
  - `sendTo()` — sends to user-selected recipients (validated email array)
  - `getAvailableRecipients()` — returns client email + contact emails with labels/types for dialog
  - Builds email from configurable template (stored in `user_settings`)
  - Variable interpolation: `{invoice_number}`, `{business_name}`, `{client_name}`, `{total}`
  - Includes payment instructions when configured
  - Attaches generated invoice PDF
- [x] Mailable: `InvoiceEmail` — markdown email template with PDF attachment
  - Configurable sections: greeting, body text, payment instructions, footer
  - Line items table in email body
  - View Invoice button linking to app
  - PDF attachment via `attachments()` method
- [x] Controller action: `InvoiceController@send` (POST `/invoices/{invoice}/send`)
  - Accepts `recipients` array (required, validated as emails)
  - Sends email with PDF, auto-updates status to "sent" if currently "draft"
- [x] Routes: `PUT /invoices/{invoice}/status`, `POST /invoices/{invoice}/duplicate`, `POST /invoices/{invoice}/send`, `GET /invoices/{invoice}/pdf`
- [ ] Email send logging (optional: `invoice_emails` table — deferred)

**PDF Generation:**
- [x] Package: `barryvdh/laravel-dompdf` v3.1
- [x] Service: `InvoicePdfService`
  - `generate()` — renders `pdf.invoice` Blade template to A4 PDF with business settings
  - `filename()` — returns `{invoice_number}.pdf`
- [x] Blade template: `resources/views/pdf/invoice.blade.php`
  - Professional layout matching Forest Ritual design tokens (colors, typography)
  - Header with business name/address, invoice number, status badge
  - Bill To section with client details
  - Line items table with unit column
  - Totals section with GCT, withholding, contractors levy, net receivable
  - Notes and payment instructions footer
- [x] Controller action: `InvoiceController@download` (GET `/invoices/{invoice}/pdf`)
  - Downloads PDF with `Content-Disposition: attachment`
- [x] Email attachment: PDF auto-attached to invoice emails

**Tests (20 tests, 171 total, 742 assertions):**
- [x] Unit field persists on create and update
- [x] Invoice number respects user's configured format (prefix, separator, padding)
- [x] Invoice number auto-increments via user settings
- [x] Status quick-update works, rejects invalid status, enforces ownership
- [x] Duplicate creates new draft with same items/unit/notes
- [x] Duplicate enforces ownership
- [x] Send email dispatches to selected recipients
- [x] Send auto-updates draft to sent, preserves non-draft status
- [x] Send includes client contacts when selected
- [x] Send requires at least one recipient, validates email format
- [x] Show page includes business settings, client contacts, and available recipients
- [x] PDF download returns application/pdf with correct filename
- [x] PDF download enforces ownership (403)
- [x] Invoice email includes PDF attachment

### Verification

- [x] User can create clients, marking some as designated entities
- [x] Invoices auto-calculate GCT, withholding tax, and contractors levy based on profile + client
- [x] Invoice list filters by status and date range
- [x] All amounts display as formatted JMD currency

---

## Phase 2 — Tax Calculation Engine

The core intelligence of the application. Everything feeds into this.

### 2.1 Tax Calculation Service

- [x] Service: `TaxCalculationService` — the central engine
  - **Inputs:** user's total gross income (paid invoices), withholding tax credits, tax profile
  - **Outputs:** structured tax breakdown

  ```
  calculateAnnualTax(User $user, int $year): TaxBreakdown
  ```

  **Calculation steps:**
  1. Gross Income = sum of all paid invoice subtotals for the year
  2. Net Income = Gross Income
  3. Income Tax (thresholds from `statutory_rates` table):
     - First JMD `tax_free_threshold` → 0%
     - Up to JMD `tax_bracket_25_limit` → 25%
     - Above `tax_bracket_25_limit` → 30%
  4. NIS Contribution = Net Income × `nis_rate` (from `statutory_rates`)
  5. Education Tax = Net Income × `education_tax_rate` (from `statutory_rates`)
  6. Total Tax Liability = Income Tax + NIS + Education Tax
  7. Withholding Tax Credits = sum of all withholding tax from invoices + manual credits
  8. Net Tax Payable = Total Tax Liability − Withholding Tax Credits

- [x] DTO: `TaxBreakdown` — structured object with all computed values
- [x] DTO: `QuarterlyEstimate` — quarter, deadline, amountDue, isPast

### 2.2 Quarterly Estimates

- [x] Service method: `calculateQuarterlyEstimates(User $user, int $year): array`
  - Divide net tax payable into 4 equal quarterly payments
  - Map to statutory deadlines: March 15, June 15, September 15, December 15
  - Track which quarters have passed and which are upcoming
  - Factor in withholding credits already applied

### 2.3 Withholding Tax Ledger

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

## Phase 3 — Dashboard & Visualizations

The primary interface users see. Surfaces all the engine outputs.

### 3.1 Main Dashboard

- [x] Controller: `DashboardController` — aggregates data via TaxCalculationService + GctMonitorService + monthly breakdowns
- [x] Page: `Pages/Dashboard.vue` (replaced placeholder with full widget dashboard)
- [x] Components:
  - `Components/Domain/TaxSummaryCard.vue` — gross income, net payable, income tax/NIS/education tax breakdown
  - `Components/Domain/QuarterlyEstimatesTimeline.vue` — 4 quarterly blocks with amount due, past/due status, deadline dates
  - `Components/Domain/WithholdingCreditsWidget.vue` — total credits with link to ledger
  - `Components/Domain/GctThresholdTracker.vue` — progress bar toward threshold with percentage + warning at 80%+

### 3.2 GCT Threshold Alert System

- [x] Service: `GctMonitorService`
  - Calculate annual turnover from sent/paid invoices
  - Return percentage toward threshold (capped at 100%)
  - Reports isRegistered status from tax profile
- [ ] Notification: `GctThresholdApproachingNotification` (database + mail channel) — deferred to Phase 6
- [ ] Listener: check threshold after each invoice is created/paid — deferred to Phase 6

### 3.3 Year Selector

- [x] Composable: `useFiscalYear.js` — manage selected tax year, navigate with query param
- [x] All dashboard widgets filter by the selected year via DashboardController
- [x] Default to current year, allow switching to previous years (5-year range)

### Verification

- [x] Dashboard loads with real aggregated data from all modules
- [x] Quarterly timeline accurately reflects past/due quarters
- [x] GCT tracker shows correct percentage
- [x] Switching fiscal year updates all widgets

---

## Phase 4 — TAJ Form Generation

The final deliverable — bridging Kova data to official government forms.

### 4.1 Form S04 / IT01 Data Mapping

- [x] Service: `TajFormService`
  - Aggregates all data for a given tax year into S04/IT01 structure via `TaxCalculationService`
  - Line item mapping:
    - Gross Professional/Business Income
    - Less: Allowable Expenses (broken down by category)
    - Net Statutory Income
    - Less: Tax-Free Threshold
    - Tax on first bracket (25%) with taxable amount
    - Tax on remaining (30%) with taxable amount
    - NIS, NHT, Education Tax contributions
    - Less: Withholding Tax Credits
    - Net Tax Payable / Refund Due
  - Returns structured data with taxpayer info, income, categorized expenses, and full computation

### 4.2 PDF Generation

- [x] Controller: `TaxFormController` (show preview with live data, generate snapshot, view saved snapshot)
- [x] Page: `Pages/Tax/FormPreview.vue`
  - On-screen preview with 5 sections mirroring TAJ S04 layout
  - Year selector for switching tax years
  - Print/Save PDF via browser print (`window.print()` with print-optimized styles)
  - Generate Snapshot button to freeze data for audit
  - Snapshot history list with ability to view any saved version
- [ ] Server-side PDF generation (requires PDF library — deferred until package is approved)

### 4.3 Tax Form History

- [x] Migration: `tax_form_snapshots` table
  ```
  user_id (FK), tax_year (int), form_type (string),
  data (json — frozen snapshot of all computed values),
  generated_at (timestamp), created_at, updated_at
  ```
- [x] Model: `TaxFormSnapshot` (belongsTo User)
- [x] Multiple regenerations preserved for audit — each snapshot is a new record

### Verification

- [x] Form preview renders all correct line items for a given tax year
- [x] Print/PDF via browser print with print-optimized layout
- [x] Multiple regenerations preserve snapshot history (3 snapshots test)
- [x] Generated values match tax calculation engine output

---

## Phase 5 — Notifications & Scheduled Tasks

Proactive reminders so users never miss a deadline.

### 5.1 Notification System

- [x] Notifications (database + mail via Mailpit in dev):
  - `QuarterlyPaymentReminderNotification` — 14 days and 3 days before each quarterly deadline
  - `GctThresholdApproachingNotification` — at 80%, 90%, 100% of GCT threshold
  - `InvoiceOverdueNotification` — when a sent invoice passes its due date
- [x] Page: `Pages/Notifications/Index.vue` — notification center with read/unread, mark as read, mark all read
- [x] Shared Inertia prop: `notifications.unreadCount` in nav with bell icon badge
- [x] Controller: `NotificationController` (index, markAsRead, markAllRead)

### 5.2 Scheduled Commands

- [x] `app/Console/Commands/SendQuarterlyReminders.php`
  - Runs daily at 08:00, checks deadlines at 14 and 3 days out
  - Deduplicates: won't re-send for same quarter + days_until combination
- [x] `app/Console/Commands/CheckOverdueInvoices.php`
  - Runs daily at 06:00, marks sent invoices past `due_date` as overdue, notifies user
  - Skips already-overdue and paid invoices
- [x] `app/Console/Commands/CheckGctThreshold.php`
  - Runs weekly (Mondays 09:00), checks turnover against threshold at 80/90/100% levels
  - Skips already GCT-registered users, deduplicates per level per year
- [x] Registered in `routes/console.php` with `Schedule`

### Verification

- [x] Overdue invoices auto-update status and trigger notification
- [x] GCT threshold alerts fire at correct percentages, skip registered users
- [x] Unread count shared in Inertia props and displayed in nav badge
- [x] Mark as read / mark all read works correctly

---

## Phase 6 — Polish & Production Readiness

### 6.1 UI/UX Polish

- [x] Forest Ritual design system applied across all pages
- [x] Responsive layout: mobile nav bar, responsive grids on all pages
- [x] Empty states for all list pages (clients, invoices, notifications, withholding credits)
- [x] Loading states: NProgress progress bar on all Inertia navigations (accent-colored, 2px top bar)
- [x] Toast notifications: PrimeVue Toast replaces inline flash divs globally (success messages via layout watcher)
- [x] Confirmation dialogs: PrimeVue ConfirmDialog on all destructive actions (delete client, invoice, withholding credit)
- [x] All `confirm()` calls replaced with `useConfirm()` dialogs
- [x] Inline flash `<div>` blocks removed from all pages (7 pages cleaned)

### 6.2 Data Integrity

- [x] Migration: performance indexes on `invoice_items.invoice_id`, `client_contacts.client_id`, `withholding_credits.date_withheld`
- [x] Cascading deletes verified on all foreign keys (all use `cascadeOnDelete()`)
- [x] Feature tests for every controller action (142 tests, 637 assertions)
- [x] Unit tests for `TaxCalculationService` with edge cases:
  - Income exactly at threshold boundaries (tax-free, 25% bracket limit)
  - Zero income
  - Income exceeding JMD $6M bracket (30% rate)
  - Withholding credits exceeding tax liability (refund scenario)

### 6.3 Security Audit

- [x] All routes behind `auth` middleware
- [x] Ownership authorization: `abort_unless` checks on Client, Invoice, WithholdingCredit, TaxFormSnapshot controllers
- [x] Scoped queries: all data access goes through `$request->user()->` relationships
- [x] Notification ownership: scoped via `$request->user()->notifications()` relationship
- [x] No sensitive data in Inertia shared props (only name, email, settings display values)
- [x] Rate limiting: `throttle:5,1` on login/register, `throttle:3,1` on forgot-password
- [x] CSRF protection on all forms (handled by Inertia)

### 6.4 Performance

- [x] Eager loading: all controllers use `->with()` / `->load()` for related data
- [x] Invoices and notifications paginated (20 per page)
- [x] Dashboard uses aggregate queries (SUM/GROUP BY) instead of loading all records

### Verification

- [x] Full Pest test suite passes (142 tests, 637 assertions)
- [x] No N+1 queries in controller actions (verified via eager loading audit)

---

## Phase 7 — Admin Portal

Separate subdomain (`admin.kova.zncn.app`) for platform administration. Controls statutory rates, user management, and subscription oversight.

### 7.1 Admin Authentication & Authorization

- [ ] Admin `role` column on `users` table (or separate `admins` table)
- [ ] Admin auth middleware — separate guard or role-based check
- [ ] Admin login page on admin subdomain
- [ ] Route group with admin middleware, served under admin subdomain
- [ ] Separate Inertia entry point or route-based subdomain handling

### 7.2 Statutory Rate Management

- [ ] Controller: `Admin\StatutoryRateController` (index, edit, update)
- [ ] Form Request: `Admin\UpdateStatutoryRateRequest`
- [ ] Pages:
  - `Pages/Admin/StatutoryRates/Index.vue` — list all rates with current values
  - `Pages/Admin/StatutoryRates/Edit.vue` — update value and effective date
- [ ] Audit log: track who changed what rate and when (optional `statutory_rate_audit_log` table)
- [ ] Rate changes take effect for all users from `effective_from` date forward

### 7.3 User Management

- [ ] Controller: `Admin\UserController` (index, show, suspend, reactivate)
- [ ] Pages:
  - `Pages/Admin/Users/Index.vue` — paginated user list with search, subscription status
  - `Pages/Admin/Users/Show.vue` — user detail, tax profile, subscription info
- [ ] Ability to suspend/reactivate user accounts
- [ ] View user's subscription status and history

### 7.4 Platform Dashboard

- [ ] Controller: `Admin\DashboardController`
- [ ] Page: `Pages/Admin/Dashboard.vue`
- [ ] Widgets: total users, active subscriptions, revenue (when billing is live), recent signups

### Verification

- [ ] Admin can log in on admin subdomain
- [ ] Admin can update statutory rates and changes reflect for all users
- [ ] Admin can view and manage user accounts
- [ ] Regular users cannot access admin routes

---

## Phase 8 — Subscription & Billing

Subscription-based access model. Pricing structure and tiers are TBD — this phase defines the infrastructure.

### 8.1 Subscription Infrastructure

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

### 8.2 Billing Integration (TBD)

- [ ] Payment gateway integration (provider TBD — Stripe, PayPal, local JM gateway)
- [ ] Webhook handling for payment events
- [ ] Invoice generation for subscription payments
- [ ] Billing history page for users

### 8.3 Admin Plan Management

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
 ├── UserSetting (1:1) — business profile, invoice config, email templates
 ├── Subscription (1:1 active)
 │    └── Plan (N:1)
 ├── Client (1:N)
 │    ├── ClientContact (1:N)
 │    └── Invoice (1:N)
 │         └── InvoiceItem (1:N) — includes `unit` field
 ├── WithholdingCredit (1:N)
 └── TaxFormSnapshot (1:N)

StatutoryRate (admin-managed, global)
Plan (admin-managed, global)
```

---

## Implementation Order & Dependencies

```
Phase 0 ─── Authentication, Tax Profile & System Settings (done)
   │
Phase 1 ─── Clients & Invoicing (done)
   │  ├── 1.1 Clients + 1.1.1 Client Enhancements (done)
   │  └── 1.2 Invoices + 1.2.1 Invoice Enhancements (done)
   │
Phase 2 ─── Tax Calculation Engine (done)
   │
Phase 3 ─── Dashboard & Visualizations (done)
   │
Phase 4 ─── TAJ Form Generation (done)
   │
Phase 5 ─── Notifications & Scheduled Tasks (done)
   │
Phase 6 ─── Polish & Production Readiness (all phases)
   │
Phase 7 ─── Admin Portal (can start after Phase 0, independent of Phases 1-5)
   │
Phase 8 ─── Subscription & Billing (depends on Phase 7)
```

Phase 7 (Admin Portal) can be developed in parallel with Phases 1-6.
Phase 8 (Subscriptions) requires Phase 7 for plan management.
