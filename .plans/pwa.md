# Kova — Progressive Web App (PWA) Plan

**Goal:** Make Kova installable on any device (mobile, tablet, desktop) with an install prompt on first visit.

**Stack:** `vite-plugin-pwa` + Workbox (auto-generated service worker)

---

## Phase 1 — Web App Manifest & Icons (Done)

- [x] PWA icon set generated via PHP GD: `icon-192.png`, `icon-512.png`, `icon-512-maskable.png`, `apple-touch-icon.png` in `public/icons/`
- [x] `public/manifest.webmanifest` — name, description, start_url `/dashboard`, standalone display, Forest Ritual colors
- [x] `app.blade.php` updated with:
  - `<link rel="manifest">`, `<meta name="theme-color">` (#172726)
  - Apple meta tags: `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `apple-mobile-web-app-title`
  - `<link rel="apple-touch-icon">` and `<link rel="icon">`

---

## Phase 2 — Service Worker via vite-plugin-pwa (Done)

- [x] Installed `vite-plugin-pwa` v1.2.0 (dev dependency)
- [x] Configured in `vite.config.js`:
  - `registerType: 'autoUpdate'` — auto-updates SW on new builds
  - `manifest: false` — uses manual `manifest.webmanifest`
  - `navigateFallback: null` — server-rendered app, no offline fallback
  - Runtime caching: `CacheFirst` for Bunny fonts (1 year) and images (30 days)
  - Precaches 6 build assets (~1.2 MB)
- [x] Build produces `public/build/sw.js` and `public/build/workbox-*.js`
- [x] Service worker auto-registered by the plugin

---

## Phase 3 — Install Prompt (Done)

- [x] Composable: `usePwaInstall.js`
  - `canInstall` — true when `beforeinstallprompt` fired (Chrome/Edge/Firefox)
  - `showIosPrompt` — true on iOS Safari (no native prompt support)
  - `showBanner` — computed, true when either prompt available AND not dismissed
  - `install()` — triggers native install dialog via deferred prompt
  - `dismiss()` — stores timestamp in `localStorage`, suppresses banner for 7 days
  - Standalone detection: hides everything if already running as installed PWA
- [x] Install banner in `AuthenticatedLayout.vue`:
  - Dark bar (`bg-dark-surface`) pinned above the navbar with slide-in/out `<Transition>`
  - **Standard install** (Chrome/Edge): "Install Kova" text + accent Install button + dismiss X
  - **iOS Safari**: "Install Kova" + inline share icon SVG + "Add to Home Screen" instruction
  - Responsive: subtitle hidden on mobile, compact layout

---

## Phase 4 — iOS & Safari Support (Done)

- [x] Apple meta tags in `app.blade.php`:
  - `apple-mobile-web-app-capable`, `apple-mobile-web-app-title`, `apple-mobile-web-app-status-bar-style` (black-translucent)
- [x] Apple touch icons: 120, 152, 167, 180px sizes in `public/icons/`
- [x] iOS Safari detection in composable: UA sniffing for iPad/iPhone + Safari (excludes CriOS/FxiOS/EdgiOS)
- [x] Custom "Add to Home Screen" banner with inline share icon shown only on iOS Safari
- [x] Standalone mode detection: `display-mode: standalone` media query + `navigator.standalone` for iOS — suppresses all prompts when already installed

---

## Implementation Notes

**What we are NOT doing (and why):**
- **Offline mode** — Kova is a data-heavy SaaS app that requires server-side tax calculations and database access. Offline-first would require complex data sync and is not justified for the use case. The service worker only caches static assets (fonts, CSS, JS).
- **Push notifications** — The app already has database + email notifications. Browser push would add complexity without clear value for the target audience.
- **Background sync** — Same reasoning as offline mode. All mutations require server validation.

**Cache strategy:**
- Static assets (JS, CSS, fonts, icons): `CacheFirst` — serve from cache, update in background
- HTML pages: `NetworkFirst` — always fetch from server (Inertia responses are dynamic)
- API calls: No caching — all data is live

**Dependencies:**
- `vite-plugin-pwa` (dev dependency) — handles manifest injection, service worker generation, and registration
- No runtime dependencies — the composable is vanilla Vue
