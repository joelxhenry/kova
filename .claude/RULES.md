# AI Coding Assistant Directives & Rules

## 1. Core Operating Principles
* **No Placeholders:** Never use `// ... existing code`, `// ... rest of the logic`, or `/* implement later */`. When asked to write or modify a file, provide the complete, functional code block unless explicitly told to provide a snippet.
* **Assume Modernity:** This project uses PHP 8.2+ and Laravel 13. Do not use outdated PHP conventions (e.g., `array()` instead of `[]`) or deprecated Laravel helpers. Use modern PHP features like constructor property promotion, match expressions, and nullsafe operators.
* **Package Manager:** Always use **`pnpm`** for frontend dependencies (e.g., `pnpm install`, `pnpm add`, `pnpm run dev`). **DO NOT** use `npm` or `yarn` under any circumstances to prevent lockfile conflicts.
* **Think Before Writing:** Before generating code, briefly state the architectural pattern you are going to use to ensure it aligns with the project guidelines.
* **Fail Gracefully:** If you do not know the exact syntax for a specific version of a package (e.g., a specific PrimeVue component API), state that you are unsure rather than hallucinating properties.

## 2. Strict Architectural Boundaries (The "Don'ts")
* **DO NOT** put business logic, complex data transformations, or third-party API calls in Controllers.
* **DO NOT** put business logic in Eloquent Models. Models are for relationships, casts, and simple scopes.
* **DO NOT** use inline validation in Controllers (e.g., `$request->validate(...)`). Always use Form Request classes for validation and authorization.
* **DO NOT** introduce state management libraries like Pinia or Vuex unless explicitly instructed. Rely on Inertia server-provided props and Vue composables.
* **DO NOT** add new `pnpm` or `composer` dependencies without asking for permission first.

## 3. Laravel & PHP Rules
* **Strict Typing:** Every new PHP file must start with `declare(strict_types=1);`.
* **Type Hinting:** Strictly type all method arguments, return types, and class properties. Use custom Data Transfer Objects (DTOs) or associative arrays with clear docblocks if returning complex data structures from Services.
* **Dependency Injection:** Use constructor injection for Services inside Controllers. Do not use the `app()` helper or facades for resolving local services.
* **Database Queries:** Always mitigate N+1 query problems. Use `->with()` to eager load relationships before returning data to the frontend via Inertia.
* **Mass Assignment:** Always define `$fillable` on Eloquent models to prevent mass assignment vulnerabilities.

## 4. Vue 3, Inertia, & Frontend Rules
* **Composition API:** Strictly use the `<script setup>` syntax for all Vue components. Do not use the Options API.
* **Inertia Forms:** Always use Inertia's `useForm()` helper for form submissions to automatically handle loading states, progress bars, and error mapping.
* **Routing:** Never use standard `<a>` tags for internal app navigation. Always use Inertia's `<Link>` component to maintain the SPA experience.
* **Props Typing:** Use `defineProps()` with clear type definitions (TypeScript interfaces or verbose JSDoc) to document what data the server is providing to the page.
* **PrimeVue Styling:** The project uses PrimeVue in **unstyled mode** via Tailwind CSS. Do not apply inline styles. Use Tailwind utility classes via the `pt` (pass-through) property if a PrimeVue component's internal elements need specific styling.

## 5. Security & Best Practices
* **Authorization First:** Always verify user authorization in the Form Request's `authorize()` method or via Laravel Policies before executing any controller logic.
* **No Secrets in Props:** Never pass sensitive data (hashed passwords, internal system tokens, unused personal user data) to the frontend via Inertia props. Only send exactly what the Vue component needs to render.
* **Error Handling:** Wrap external API calls or complex database transactions inside `try/catch` blocks within the Service class. Throw custom exceptions or return standardized error formats that the Controller can pass back to the user smoothly.

## 6. Testing Requirements
* **Test Before Done:** Every task that introduces or modifies backend logic (controllers, services, form requests, models, routes) **must** include automated Pest tests that pass before the task can be marked as completed.
* **What to Test:** Write feature tests for controller actions (HTTP status codes, redirects, validation errors, authorization). Write unit tests for service classes (calculations, business logic, edge cases).
* **Test Must Pass:** Run `php artisan test` (or `make test` in Docker) and confirm all tests pass. A task with failing tests is not complete — fix the code or the test before marking it done.
* **Test Location:** Feature tests go in `tests/Feature/`, unit tests in `tests/Unit/`. Mirror the app structure (e.g., `tests/Feature/Auth/LoginTest.php` for `LoginController`).

## 7. Self-Correction Mandate
* If you generate code that violates any of the rules above, and you catch it during your own generation process, immediately rewrite it to comply before finishing the response.