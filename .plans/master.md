# Kova — Master Implementation Plan

**Product:** Free Invoice & Expense Management Platform
**Stack:** Laravel 13 · Vue 3 · Inertia.js · PrimeVue · Tailwind CSS 4

---

## Phase 0 — Foundation

### 0.1 Authentication
- [x] Login, Register, Forgot Password, Reset Password
- [x] Session-based auth with CSRF protection
- [x] Rate limiting on auth routes (5/min login, 3/min forgot-password)

### 0.2 System Settings
- [x] UserSetting model with JSON settings column
- [x] Settings groups: business profile, invoicing, email templates
- [x] Business profile: name, address, phone, email, payment terms, payment instructions
- [x] Invoice numbering: prefix, separator, next number, padding with live preview
- [x] Email templates with variable interpolation ({invoice_number}, {business_name}, {client_name}, {total})

---

## Phase 1 — Clients & Invoicing

### 1.1 Clients
- [x] Client CRUD with name, email, phone, TRN (encrypted), address fields
- [x] Client contacts (1:N) with first/last name, email, phone
- [x] Client show page with financial summary and invoice history

### 1.2 Invoices
- [x] Invoice CRUD with line items (description, unit, quantity, unit price)
- [x] Configurable invoice numbering via UserSettingService
- [x] Invoice statuses: draft, sent, paid, overdue, cancelled
- [x] Quick actions: status update, duplicate (→ edit page), send email, download PDF
- [x] Professional invoice view page with business info, bill-to, line items, totals
- [x] PDF generation via barryvdh/laravel-dompdf
- [x] Email sending with recipient selection dialog and PDF attachment
- [x] Automatic overdue detection via scheduled command

---

## Phase 2 — Expense Tracking

- [x] ExpenseCategory model with 7 system defaults (Equipment, Fuel & Transport, Office Rent, Software & Subscriptions, Professional Services, Utilities, Other)
- [x] Expense CRUD with category, description, amount, date, notes
- [x] Expense list with category filter and date range filter
- [x] Paginated expense list

---

## Phase 3 — Dashboard

- [x] Stats cards: total invoiced (paid), total expenses, pending invoices, overdue count
- [x] Recent invoices list (5 most recent with client, amount, status)
- [x] Recent expenses list (5 most recent with category, amount)

---

## Phase 4 — Notifications

- [x] InvoiceOverdueNotification (database + mail)
- [x] Scheduled command: check-overdue-invoices (daily 06:00)
- [x] Notification center page with read/unread
- [x] Notification dropdown in nav bar with recent items
- [x] Unread count badge

---

## Phase 5 — Admin Portal

- [x] Admin role via is_admin boolean on users table
- [x] EnsureAdmin middleware
- [x] Admin routes under /admin prefix with dedicated AdminLayout
- [x] Admin dashboard: user stats (total, active, suspended)
- [x] User management: list (search + filter), detail page, suspend/reactivate
- [x] Suspended users blocked via EnsureNotSuspended middleware

---

## Phase 6 — Polish & Production Readiness

- [x] NProgress loading bar on all navigations
- [x] PrimeVue Toast for flash messages (replaces inline divs)
- [x] PrimeVue ConfirmDialog for destructive actions
- [x] Mobile-responsive: reduced padding, stacked layouts, card-based line items
- [x] PrimeIcons installed
- [x] Mobile input text sizing
- [x] Mobile dialog/toast width constraints
- [x] Database performance indexes
- [x] Rate limiting on auth routes
- [x] All ownership checks verified

---

## Phase 7 — PWA

- [x] Web app manifest with icons (192, 512, maskable, Apple sizes)
- [x] Service worker via vite-plugin-pwa (CacheFirst for fonts/images)
- [x] Install prompt composable with 7-day dismiss
- [x] iOS Safari "Add to Home Screen" instructions
- [x] Standalone mode detection

---

## Phase 8 — Landing Page

- [x] Standalone Blade landing page at / (no Inertia)
- [x] Hero: "Invoicing made simple" + CTA
- [x] Features grid: 6 features (invoicing, expenses, PDF/email, clients, status tracking, free forever)
- [x] Final CTA section with dark background
- [x] Footer with login/signup links
- [x] Scroll animations, meta tags, OG image
- [x] Authenticated users redirect to /dashboard

---

## Data Model Summary

```
User
 ├── UserSetting (1:1) — business profile, invoice config, email templates
 ├── Client (1:N)
 │    ├── ClientContact (1:N)
 │    └── Invoice (1:N)
 │         └── InvoiceItem (1:N)
 └── Expense (1:N)
      └── ExpenseCategory (N:1)
```

---

## Test Coverage

77 tests, 401 assertions — covering clients, invoices (CRUD, enhancements, PDF, email), expenses, settings, dashboard, admin (auth, user management), and landing page.
