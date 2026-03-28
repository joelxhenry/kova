# Product Requirements Document (PRD)
**Project:** Kova — Invoice & Expense Management
**Tech Stack:** Laravel 13, Vue.js 3, Inertia.js, PrimeVue

---

## 1. Product Objective
Kova is a free, intuitive invoicing and expense management platform for freelancers, independent contractors, and small businesses. It provides professional invoice generation, client management, expense tracking, and financial overview — all in one place.

## 2. Target Audience
* Freelancers and independent contractors
* Small business owners
* Consultants and service providers

---

## 3. Functional Requirements

### 3.1. Client Management
* Manage clients with contact details, addresses, and multiple contacts per client
* Track invoice history per client with financial summary (total invoiced, balance due)

### 3.2. Invoice Management
* Full invoice lifecycle: create, edit, send, duplicate, and track status (draft, sent, paid, overdue, cancelled)
* Line items with description, unit, quantity, and unit price
* Configurable invoice numbering (prefix, separator, padding)
* Professional PDF generation and email delivery to clients
* Automatic overdue detection with notifications

### 3.3. Expense Tracking
* Log business expenses with categories (Equipment, Fuel & Transport, Office Rent, Software & Subscriptions, Professional Services, Utilities, Other)
* Filter expenses by category and date range
* Receipt upload support

### 3.4. Dashboard
* Financial overview: total invoiced, total expenses, pending invoices, overdue invoices
* Recent invoices and expenses at a glance

### 3.5. User Settings
* Business profile (name, address, phone, email)
* Invoice numbering configuration with live preview
* Email templates with variable interpolation ({invoice_number}, {business_name}, {client_name}, {total})
* Payment terms and instructions

### 3.6. Admin Portal
* User management (view, suspend, reactivate)
* Platform metrics (total users, active, suspended)

### 3.7. Platform
* Free to use — no subscriptions or payment required
* PWA installable on mobile and desktop
* Overdue invoice notifications (database + email)

---

## 4. Success Metrics
* Number of active users creating invoices monthly
* Invoice send rate (invoices emailed vs created)
* User retention across monthly periods
