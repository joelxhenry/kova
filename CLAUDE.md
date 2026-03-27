# Kova — Contractor Income & Tax Management Tool (Jamaica)

## Tech Stack
- **Backend:** Laravel 13, PHP 8.4 (strict types)
- **Frontend:** Vue 3 (Composition API, `<script setup>`), Inertia.js
- **UI:** PrimeVue 4 (unstyled mode), Tailwind CSS 4
- **Package Manager:** pnpm (never npm/yarn)
- **Database:** SQLite (local dev)
- **Testing:** Pest

## Architecture
- **Thin Controller / Fat Service** pattern — see ARCHITECTURE.md
- Form Request classes for all validation
- Service classes in `app/Services/` for business logic
- No business logic in Controllers or Models
- No Pinia/Vuex — use Inertia props + Vue composables

## Key Commands
- `pnpm run dev` — Vite dev server
- `php artisan serve` — Laravel dev server
- `composer run dev` — Run both concurrently
- `php artisan test` — Run Pest tests

## Project Docs
- `PRODUCT.md` — Business requirements, tax logic, Jamaican TAJ rules
- `ARCHITECTURE.md` — Data flow, directory structure, coding standards
- `RULES.md` — Strict coding rules and anti-patterns
- `DESIGN.xml` — Bold Typography design system tokens and component specs

## Rules
- Always use `declare(strict_types=1);` in PHP files
- Always use `<script setup>` in Vue components
- Use Inertia's `useForm()` for form submissions
- Use PrimeVue `pt` prop for styling (unstyled mode + Tailwind)
- No inline CSS, no new UI libraries without permission
- No new dependencies without permission
