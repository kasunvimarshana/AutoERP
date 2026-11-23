# 2026-11-23 - Service Job Feedback CRUD Deep Audit, Concurrency, and Accessibility Remediation

**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>) | Phone: +94713525253  
**Lead Architect & Developer:** Kasun Vimarshana  
**Repository:** https://github.com/kasunvimarshana/AutoERP  
**Modules Affected:** `app/Modules/VehicleService`, `resources/js/modules/vehicle-service`, `.agents/skills/autoerp-engineering`

---

## 1. Context & Objectives

Under directives `/compact`, `/learn`, `/plan`, `/boost`, and `/goal`, we conducted an exhaustive, forensic end-to-end deep audit and inspection of the **Service Job Feedback CRUD** subsystem across database migrations, backend models, DTOs, form requests, controllers, services, reporting aggregates, frontend UI components, and API contracts.

The objective was to identify and fix all existing bugs, optimistic locking gaps, performance bottlenecks, MySQL schema incompatibilities, data overwriting issues, and UI/UX shortcomings, ensuring strict compliance with the 12 Core Engineering Axioms in `AGENTS.md`.

---

## 2. Root Causes Identified & Addressed

1. **Parent Job Version Desynchronization on Create (`expected_job_version`):**
   - In `VehicleServiceFeedbackDialog.tsx`, the dialog submitted `expected_job_version: expectedJobVersion`.
   - In `StoreVehicleServiceJobFeedbackRequest.php`, the rule for `expected_job_version` was missing, and `toData()` only read `expected_version`.
   - Consequently, `expectedJobVersion` was evaluated as `null`, skipping the parent job's version increment on the database while the frontend incremented its local job version via `onChanged(expectedVersion + 1)`. Any subsequent edit to the job resulted in an unrecoverable 422 Concurrency error.
2. **Missing `lockForUpdate` & Concurrency Gaps in Update/Delete:**
   - In `VehicleServiceJobFeedbackService::update()` and `delete()`, feedback and parent job models were evaluated without `lockForUpdate()`. Concurrent requests could race and cause lost updates without optimistic concurrency detection.
   - In `delete()`, `expectedJobVersion` was not accepted or checked, and parent job version was never bumped on delete.
3. **Silent Data Loss on Partial Update (PUT/PATCH):**
   - In `UpdateVehicleServiceJobFeedbackRequest::toData()`, omitted attributes were evaluated as `null` instead of distinguishing between omitted fields and explicitly cleared fields.
   - In `VehicleServiceJobFeedbackService::update()`, omitted fields unconditionally overwritten existing attributes with `null`.
4. **MySQL 1054 Column Not Found Error on Eager Loading:**
   - `VehicleServiceJobFeedbackService` eager-loaded `user:id,name,email`. In `UserModel`, `name` is a computed accessor (`getNameAttribute()`), not a database column.
   - In MySQL, selecting non-existent column `name` threw `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'name' in 'field list'`. Additionally, omitting `first_name` and `last_name` caused `user->name` to evaluate to `""`, causing author display to fall back to `'Staff'`.
5. **Author Name Static Overwrite in Frontend Edit Form:**
   - When feedback had `author_name = null` (dynamically linked to staff user), `VehicleServiceJobFeedbackResource` returned `author_name` as the resolved name. Editing prefilled the input with this string and saved it as a static string into the DB.
6. **UI/UX & Accessibility Shortcomings:**
   - Missing "Customer visible only" filter toggle in the toolbar.
   - Category breakdown badges (`Reviews`, `Notes`, `Feedback`, `Complaints`) were static instead of clickable filters.
   - Star rating radiogroup lacked keyboard arrow navigation (`ArrowLeft`, `ArrowRight`, `ArrowUp`, `ArrowDown`).
   - Deletion errors were not displayed inside the confirmation modal.
   - Edited feedback entries had no `(edited)` visual indicator.

---

## 3. Changes Made

### 3.1 Backend DTO, Form Requests & Resources
- **`app/Modules/VehicleService/DTOs/VehicleServiceJobFeedbackData.php`**:
  - Added presence flags: `$hasFeedbackType`, `$hasComment`, `$hasRating`, `$hasTitle`, `$hasIsCustomerVisible`, `$hasAuthorName`.
- **`app/Modules/VehicleService/Http/Requests/StoreVehicleServiceJobFeedbackRequest.php`**:
  - Added validation rule `'expected_job_version' => ['nullable', 'integer', 'min:1']`.
  - Mapped `$this->input('expected_job_version') ?? $this->input('expected_version')` in `toData()`.
  - Set all `$has*` presence flags to `true`.
- **`app/Modules/VehicleService/Http/Requests/UpdateVehicleServiceJobFeedbackRequest.php`**:
  - Preserved field presence flags (`$this->has(...)`) to distinguish between omitted fields and explicitly cleared fields (`null`/`""`).
- **`app/Modules/VehicleService/Http/Requests/DeleteVehicleServiceJobFeedbackRequest.php`**:
  - Added validation rule `'expected_job_version' => ['nullable', 'integer', 'min:1']`.
- **`app/Modules/VehicleService/Http/Resources/VehicleServiceJobFeedbackResource.php`**:
  - Added `'raw_author_name' => $this->author_name === null ? null : (string) $this->author_name`.
  - Preserved `'author_name' => (string) $authorDisplay` for backward compatibility.

### 3.2 Backend Service & Controller
- **`app/Modules/VehicleService/Services/VehicleServiceJobFeedbackService.php`**:
  - Fixed eager loading columns: changed `user:id,name,email` to `user:id,first_name,last_name,email,username`, and `creator`/`updater` to `id,first_name,last_name`.
  - In `create()`: re-queried parent job with `lockForUpdate()` when `expectedJobVersion` is supplied, asserted expected version, and bumped job `row_version`.
  - In `update()`: re-queried feedback record with `lockForUpdate()`. Re-queried parent job with `lockForUpdate()` when `expectedJobVersion` is supplied and bumped job version. Only updated fields that were explicitly provided in the request payload (`$data->has*`).
  - In `delete()`: accepted `?int $expectedJobVersion = null`. Re-queried feedback with `lockForUpdate()`. Re-queried parent job with `lockForUpdate()` and bumped job version when `expectedJobVersion` is provided.
- **`app/Modules/VehicleService/Http/Controllers/VehicleServiceJobFeedbackController.php`**:
  - In `show()`: eager-loaded relations (`user`, `creator`, `updater`) to eliminate N+1 queries.
  - In `destroy()`: passed `expected_job_version` to `service->delete()`.

### 3.3 Frontend Contracts, API & UI Components
- **`resources/js/modules/vehicle-service/vehicleServiceTypes.ts`**:
  - Added `raw_author_name?: string | null;` to `VehicleServiceJobFeedback`.
- **`resources/js/modules/vehicle-service/api/feedbacks.ts`**:
  - Updated `deleteVehicleServiceFeedback` to accept and send `expectedJobVersion`.
- **`resources/js/modules/vehicle-service/components/VehicleServiceFeedbackDialog.tsx`**:
  - Initialized `authorName` state with `feedback?.raw_author_name ?? ''` (leaving blank with hint when no custom author was specified).
  - Added keyboard arrow navigation (`ArrowLeft`, `ArrowRight`, `ArrowUp`, `ArrowDown`) to star rating buttons.
  - Added `inline` prop to `<ErrorAlert error={error} inline />`.
- **`resources/js/modules/vehicle-service/components/VehicleServiceFeedbackTab.tsx`**:
  - Added `key={editingFeedback?.id ?? 'create'}` to `<VehicleServiceFeedbackDialog>`.
  - Added "Customer visible only" checkbox filter to the toolbar.
  - Converted category metric badges into interactive filter toggle buttons.
  - Rendered `<ErrorAlert error={actionError} inline />` inside `ConfirmDialog` message.
  - Added `(edited)` indicator on cards when `item.updated_at && item.updated_at !== item.created_at`.
  - Passed `expectedVersion` (job version) as `expectedJobVersion` to `deleteVehicleServiceFeedback`.

### 3.4 Engineering Skill Memory (`.agents/skills/autoerp-engineering/SKILL.md`)
- Added **Explicit Optional Form Defaulting & Field Presence** standard.
- Added **Blade Template Sandboxing & Tenant Layout Isolation** standard.
- Added **Token Rotation Grace Window** standard.
- Expanded Prohibited Architectural Anti-Patterns from 8 to 10 (Anti-Pattern 9: Omitted Field Overwrites; Anti-Pattern 10: Unchecked Token Revocation on Concurrency).

---

## 4. Modified & Created Files Summary

| File Path | Description of Changes |
|---|---|
| `.agents/skills/autoerp-engineering/SKILL.md` | Added standards for form defaulting, blade sandboxing, token rotation grace, and anti-patterns 9 & 10. |
| `app/Modules/VehicleService/DTOs/VehicleServiceJobFeedbackData.php` | Added `$has*` presence flags for selective field mutation. |
| `app/Modules/VehicleService/Http/Requests/StoreVehicleServiceJobFeedbackRequest.php` | Validated `expected_job_version` and normalized `toData()`. |
| `app/Modules/VehicleService/Http/Requests/UpdateVehicleServiceJobFeedbackRequest.php` | Preserved field presence flags to prevent omitted field overwrites. |
| `app/Modules/VehicleService/Http/Requests/DeleteVehicleServiceJobFeedbackRequest.php` | Validated `expected_job_version`. |
| `app/Modules/VehicleService/Http/Resources/VehicleServiceJobFeedbackResource.php` | Added `raw_author_name` and preserved `author_name`. |
| `app/Modules/VehicleService/Services/VehicleServiceJobFeedbackService.php` | Fixed eager loading columns (`first_name, last_name`), added `lockForUpdate` on feedback and parent job, selective field updates, and job version bumping on delete. |
| `app/Modules/VehicleService/Http/Controllers/VehicleServiceJobFeedbackController.php` | Eager-loaded relations in `show()`, passed `expected_job_version` in `destroy()`. |
| `app/Modules/VehicleService/Tests/VehicleServiceJobFeedbackTest.php` | Added tests for partial updates, field clearing to null, parent job version bumps on create/delete, and raw author name resolution. |
| `resources/js/modules/vehicle-service/vehicleServiceTypes.ts` | Added `raw_author_name?: string | null` to `VehicleServiceJobFeedback`. |
| `resources/js/modules/vehicle-service/api/feedbacks.ts` | Supported `expectedJobVersion` in `deleteVehicleServiceFeedback`. |
| `resources/js/modules/vehicle-service/components/VehicleServiceFeedbackDialog.tsx` | Used `raw_author_name` for empty author default, added arrow key rating navigation, and inline error alert. |
| `resources/js/modules/vehicle-service/components/VehicleServiceFeedbackTab.tsx` | Added customer visibility filter toggle, interactive category buttons, inline error in confirm dialog, `(edited)` indicator, and job version sync on delete. |
| `resources/js/modules/vehicle-service/components/VehicleServiceFeedbackTab.test.tsx` | Updated delete assertion to check expected job version and added tests for customer visibility toggle and category badge filters. |

---

## 5. Verification & Quality Gate Results

| Quality Gate | Tooling / Command | Result |
|---|---|---|
| **TypeScript Typecheck** | `npm run typecheck` | **PASS** (0 errors) |
| **ESLint Analysis** | `npx eslint resources/js/modules/vehicle-service/...` | **PASS** (0 errors, 0 warnings) |
| **Backend Code Style (Pint)** | `php vendor/bin/pint --test ...` | **PASS** (100% PSR-12 compliant, 0 violations) |
| **Migration Baseline Integrity** | `php artisan test --filter ModuleMigrationBaselineTest` | **PASS** (1 passed, 3 assertions) |
| **Historical Retention Integrity** | `php artisan test --filter HistoricalRetentionMigrationTest` | **PASS** (1 passed, 22 assertions) |
| **Backend Service Job Feedback Tests** | `php artisan test app/Modules/VehicleService/Tests/VehicleServiceJobFeedbackTest.php` | **PASS** (15 passed, 122 assertions) |
| **Backend Reporting Tests** | `php artisan test app/Modules/Reporting/Tests/ServiceJobFeedbackReportTest.php` | **PASS** (7 passed, 52 assertions) |
| **All Vehicle Service Backend Tests** | `php artisan test app/Modules/VehicleService/Tests` | **PASS** (110 passed, 805 assertions) |
| **Frontend Unit Tests** | `npx vitest run ...VehicleServiceFeedbackTab.test.tsx ...ServiceJobFeedbackReportPage.test.tsx` | **PASS** (15 passed, 2 test files) |
| **All Vehicle Service Frontend Tests** | `npx vitest run resources/js/modules/vehicle-service/` | **PASS** (110 passed, 20 test files) |
| **Production Vite Build** | `npm run build` | **PASS** (Built in 8.80s, 791 modules transformed) |

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [https://github.com/kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
