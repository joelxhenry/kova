# Project Architecture & Tech Stack Guidelines

## 1. Tech Stack Overview
* **Backend Framework:** Laravel 13
* **Frontend Framework:** Vue.js 3 (Composition API, `<script setup>`)
* **Bridge/Routing:** Inertia.js (Server-driven single-page app experience)
* **UI Component Library:** PrimeVue (Unstyled mode with Tailwind CSS recommended)
* **Styling:** Tailwind CSS (Utility classes for layout and custom styling)

## 2. Architectural Philosophy: The "Thin Controller" Pattern
This project strictly adheres to the **Modular Monolith** approach, utilizing the Thin Controller / Fat Service architecture. 

Controllers are strictly traffic cops. They must **never** contain business logic, complex database queries, or raw validation arrays. Their only responsibilities are:
1. Receiving the request.
2. Triggering validation via Form Request classes.
3. Passing the validated data to a Service class.
4. Returning the Service class's result via an Inertia response or Redirect.

## 3. Data Flow & Request Lifecycle
When an HTTP request enters the application, it must follow this exact lifecycle:

### Step 1: Validation (Form Request Classes)
* **Location:** `app/Http/Requests/`
* **Rule:** All incoming data (POST, PUT, PATCH) must be validated using a dedicated Form Request class (e.g., `StoreInvoiceRequest`).
* **Responsibility:** Handles authorization (`authorize()` method) and data validation rules (`rules()` method). This ensures the Controller only ever works with safe, validated data.

### Step 2: The Controller (Traffic Routing)
* **Location:** `app/Http/Controllers/`
* **Rule:** Controllers inject the Form Request (for validation) and the necessary Service classes.
* **Responsibility:** Extracts the validated data (`$request->validated()`), passes it to the Service, and returns the Inertia view or redirect. 

### Step 3: Business Logic (Service Classes)
* **Location:** `app/Services/`
* **Rule:** This is where the actual work happens. Services handle complex calculations, third-party API calls, and orchestrate multiple model interactions.
* **Responsibility:** For example, an `InvoiceService` will handle calculating the 3% withholding tax, attaching the correct GCT rate, and generating the PDF. 

### Step 4: Data Access (Eloquent Models)
* **Location:** `app/Models/`
* **Rule:** Models represent the database structure. They are called primarily by Service classes (and sometimes Controllers for simple read-only `index` methods).
* **Responsibility:** Holds relationships, accessors, mutators, and query scopes. Do not place business logic (like tax calculation algorithms) inside the model.

## 4. Backend File Structure (Laravel)
Maintain strict separation of concerns within the `app/` directory.

* `app/Http/Controllers/`: Grouped by domain (e.g., `InvoiceController`).
* `app/Http/Requests/`: Strict validation rules (e.g., `StoreInvoiceRequest`, `UpdateTaxProfileRequest`).
* `app/Services/`: Core application logic (e.g., `TaxCalculationService.php`).
* `app/Models/`: Eloquent models with relationships and scopes.
* `app/DataTransferObjects/` (Optional): Use DTOs for passing highly structured data between Controllers and Services if associative arrays become too ambiguous.

## 5. Frontend File Structure (Vue/Inertia)
The `resources/js/` directory drives the frontend.

* `resources/js/Pages/`: Inertia entry points mapping directly to URLs (e.g., `Pages/Invoices/Create.vue`).
* `resources/js/Layouts/`: Persistent layouts (e.g., `AuthenticatedLayout.vue`).
* `resources/js/Components/`: Reusable UI elements.
    * `Components/UI/`: Base wrappers for PrimeVue components.
    * `Components/Domain/`: Feature-specific components (e.g., `TaxBreakdownChart.vue`).
* `resources/js/Composables/`: Reusable Vue logic (e.g., `useCurrencyFormatter.js`).

## 6. Coding Standards & Best Practices

### Backend (PHP/Laravel)
* Use strict typing (`declare(strict_types=1);`) in all new PHP files.
* Always use Eloquent eager loading (`with()`) in Controllers/Services to prevent N+1 query problems before passing data to Inertia.
* Type-hint everything: method arguments, return types, and class properties.

### Frontend (Vue/Inertia)
* Always use the `<script setup>` syntax.
* Handle form submissions using Inertia's `useForm()` helper to seamlessly catch validation errors thrown by Laravel's Form Requests.
* Leverage PrimeVue's UI components to display the validation errors returned by Inertia props.