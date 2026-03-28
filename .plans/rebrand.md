# Kova Rebrand — Invoice & Expense Management Platform

**Goal:** Strip Kova down to a clean, free invoice and expense management tool. Remove all tax calculations, billing/subscriptions, statutory rates, TAJ forms, and related complexity.

---

## What stays
- Client management (clients, contacts, addresses)
- Invoicing (create, edit, send, duplicate, PDF, status tracking)
- User settings (business profile, invoice numbering, email templates)
- Notifications (overdue invoices)
- Admin portal (users only — no statutory rates)
- PWA
- Landing page (updated copy)

## What gets added back
- Expense categories (system defaults)
- Expense tracking (CRUD with categories, receipt upload)

## What gets removed
- Tax calculation engine (TaxCalculationService, TaxBreakdown DTO, QuarterlyEstimate DTO)
- Tax profile (model, controller, migration reference)
- Statutory rates (model, admin management, versioning, audit log)
- Withholding tax logic on invoices (WHT, contractors levy)
- GCT logic on invoices
- GCT threshold monitoring (GctMonitorService, notifications)
- Quarterly payment estimates
- TAJ form generation (TajFormService, TaxFormController, snapshots)
- Withholding credits (model, controller, service)
- Billing/Subscription (Paddle, Cashier, EnsureSubscribed middleware)
- Trial banner
- Dashboard widgets (TaxSummaryCard, QuarterlyEstimatesTimeline, WithholdingCreditsWidget, GctThresholdTracker)
- Scheduled commands (quarterly reminders, GCT threshold checks)
- Related notifications (QuarterlyPaymentReminder, GctThresholdApproaching)
- All related tests, Vue pages, and components

## Phases

### Phase 1 — Remove billing & subscription
### Phase 2 — Remove tax engine, profiles, statutory rates, TAJ forms, withholding credits
### Phase 3 — Simplify invoices (remove WHT, GCT, contractors levy)
### Phase 4 — Add back expenses
### Phase 5 — Refactor dashboard
### Phase 6 — Update landing page, PRODUCT.md, master plan
### Phase 7 — Clean up admin, notifications, tests
