# Kova — Landing Page Plan

**Goal:** A single-page marketing site at `/` for unauthenticated visitors. Converts visitors to sign-ups. Clean, fast, Forest Ritual design system.

**Route:** `GET /` — serves a Blade view (no Inertia, no auth required). Authenticated users redirect to `/dashboard`.

---

## Phase 1 — Page Structure & Hero

The core page shell with navigation, hero section, and footer.

- [x] Create `resources/views/landing.blade.php` as a standalone Blade view (not Inertia)
- [x] Update `routes/web.php`: change `/` from redirect to landing page, redirect to dashboard if authenticated
- [x] **Navbar:**
  - Kova logo (left)
  - Anchor links: Features, Pricing (smooth scroll)
  - Login + Sign Up buttons (right)
  - Mobile: hamburger menu or minimal inline links
- [x] **Hero section:**
  - Headline: concise value proposition (e.g. "Tax season, sorted.")
  - Subheading: one-liner explaining what Kova does for Jamaican contractors
  - Primary CTA button: "Get Started Free" → `/register`
  - Secondary CTA: "See Pricing" → scroll to pricing
- [x] **Footer:**
  - Kova logo + tagline
  - Links: Login, Register, Privacy Policy (placeholder), Terms (placeholder)
  - Copyright line

### Verification
- [x] Page loads at `/` without authentication
- [x] Authenticated users redirected to `/dashboard`
- [x] Navbar links scroll smoothly to sections
- [x] Responsive on mobile

---

## Phase 2 — Features Section

Showcase what the platform does. No screenshots needed — use icons and concise copy.

- [x] **Section heading:** "Everything you need to stay compliant"
- [x] **Feature grid** (2 columns on desktop, 1 on mobile), each card has:
  - PrimeIcon or simple SVG icon
  - Title (short)
  - Description (1-2 sentences)
- [x] **Features to highlight:**
  1. **Professional Invoicing** — Create, send, and track invoices with automatic GCT, withholding tax, and contractors levy calculations.
  2. **Tax Calculation Engine** — Real-time progressive tax brackets, NIS, NHT, and Education Tax computed from your paid invoices.
  3. **Quarterly Estimates** — Know exactly what you owe before each TAJ deadline — March, June, September, December.
  4. **Withholding Tax Ledger** — Track every dollar withheld at source. Auto-credited from paid invoices.
  5. **GCT Threshold Monitoring** — Live progress toward the $15M registration threshold with automatic alerts.
  6. **TAJ Form Generation** — S04 form data pre-filled and ready to copy to the TAJ portal.
  7. **Client Management** — Contacts, addresses, designated entity tracking, invoice history per client.
  8. **PDF & Email** — Download professional PDF invoices or email them directly to clients with one click.

### Verification
- [x] All 8 features render in a clean grid
- [x] Icons are visible and consistent
- [x] Responsive: stacks to single column on mobile

---

## Phase 3 — Pricing Section

Mirror the in-app pricing page but styled for the landing page context.

- [x] **Section heading:** "Simple, transparent pricing"
- [x] **Pricing card** (single plan — Kova Pro):
  - Monthly / Yearly toggle
  - Price display ($9/mo or $86/yr)
  - "14-day free trial" badge
  - Feature checklist (same as in-app pricing)
  - CTA button: "Start Free Trial" → `/register`
- [x] **Below pricing:** brief trust line (e.g. "No credit card required to start. Cancel anytime.")

### Verification
- [x] Monthly/yearly toggle works
- [x] CTA links to registration
- [x] Card looks good on all breakpoints

---

## Phase 4 — Final CTA & Polish

A closing call-to-action section and overall polish.

- [x] **CTA section** (above footer):
  - Dark background (`bg-dark-surface`)
  - Headline: "Ready to take control of your taxes?"
  - Subheading: "Join Jamaican contractors who use Kova to stay ahead of TAJ deadlines."
  - Large "Get Started Free" button
- [x] **Polish:**
  - Smooth scroll behavior for anchor links
  - Subtle scroll animations (fade-in on scroll) — CSS only, no JS library
  - Meta tags: title, description, OG image for social sharing
  - Favicon already set from PWA icons
- [x] **Performance:**
  - No Inertia/Vue loaded on landing page (pure Blade + Tailwind)
  - Fonts already preconnected from app layout
  - Minimal JS (just smooth scroll + mobile menu toggle)

### Verification
- [x] Full page flows naturally: Hero → Features → Pricing → CTA → Footer
- [x] Page weight is minimal (no Vue/Inertia bundle loaded)
- [x] Mobile layout is clean with no horizontal scroll
- [x] All CTAs link to `/register`

---

## Design Notes

- **Colors:** Forest Ritual tokens — `#172726` foreground, `#FAFAFA` background, `#F95831` accent, `#D3E2DE` muted, `#243F3D` dark surface
- **Typography:** Figtree font (already loaded via Bunny CDN)
- **Spacing:** Generous vertical rhythm between sections (py-20 md:py-32)
- **Style:** Clean, editorial, minimal. No stock photos. Let the copy and whitespace do the work.
- **No JavaScript frameworks on landing page** — pure Blade + Tailwind CSS compiled via Vite. This keeps the page fast and avoids loading the full Vue/Inertia bundle.
