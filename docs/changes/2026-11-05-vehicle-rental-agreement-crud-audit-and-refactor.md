# Vehicle Rental: Agreement CRUD Comprehensive Audit and Inconsistency Hardening

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose

A forensic audit of the Agreement CRUD lifecycle revealed multiple domain inconsistencies, UX deadlocks, and missed database index utilizations:
1. **Filtering by Status Prohibited**: While migrations established the composite index `['tenant_id', 'organization_unit_id', 'status']` (`vrca_context_ix`, `vroa_context_ix`), `AgreementRequest` unconditionally marked `'status' => ['prohibited']`. This prevented query filtering by status (`GET /customer/agreements?status=active`), forcing operators to paginate through all historic agreements without a status filter.
2. **Successor Lineage Visibility & Guarding**: Predecessor agreements only had `supersedesAgreement` (belongsTo), but lacked a forward `successorAgreement` (hasOne) relation. When a successor draft existed, `AgreementResource` did not expose it, and the UI displayed a misleading "Create successor" button. Attempting to create a duplicate successor crashed with an uninformative 409 unique constraint error rather than a clear domain message.
3. **Cross-Page Navigation Query Loss**: Navigating from `VehicleUseRegisterPage` (`/vehicle-rental/customer/agreements?search=...`) failed to initialize search query state because `AgreementsPage` only inspected `vehicle_id` from `window.location.search`.
4. **Owner Agreement Vehicle Uses Parity**: `CustomerAgreement` defined `vehicleUses(): HasMany`, while `OwnerAgreement` was missing its `vehicleUses()` relation.
5. **Rate Term Typing Flexibility**: In `AgreementValidation`, `'terms.*' => ['nullable', 'string', 'regex:...']` threw string validation errors if API consumers submitted numeric integers or floats (e.g., `{"base_rate": 5000}`).
6. **Date Boundary Inputs**: `AgreementEditor` allowed start dates to be selected after end dates, and `AgreementSuccessorForm` lacked `max` constraints tied to end dates.
7. **Deletion Lifecycle Invariant**: Validated that `Agreement::delete()` throws `LogicException: Agreement history must be preserved.` to ensure hard deletes remain strictly prohibited in favor of auditable closures.

## Changes Made

### Backend

1. **`app/Modules/VehicleRental/Models/Agreement.php`**:
   - Added `successorAgreement(): HasOne` relation mapping `supersedes_agreement_id` on the same table.

2. **`app/Modules/VehicleRental/Models/OwnerAgreement.php`**:
   - Added `vehicleUses(): HasMany` relation mapping `owner_agreement_id` on `vehicle_rental_uses`, restoring relational parity with `CustomerAgreement`.

3. **`app/Modules/VehicleRental/Services/AgreementValidation.php`**:
   - Normalized integer/float numeric values in `terms` to strings prior to validation, allowing numeric payloads from API consumers while enforcing standard decimal regex patterns.

4. **`app/Modules/VehicleRental/Services/AgreementService.php`**:
   - Updated `list()` to accept optional `?AgreementStatus $status = null` and filter agreements using the `vrca_context_ix` / `vroa_context_ix` status index.
   - In `successor()`, proactively checked if a successor agreement already exists for the predecessor, throwing a human-readable `ConflictHttpException` identifying the existing successor reference (`"A successor draft ({reference}) already exists for this agreement..."`).
   - Added `'successorAgreement'` to eager-loaded relations in `relations()`.

5. **`app/Modules/VehicleRental/Http/Requests/AgreementRequest.php`**:
   - Permitted `status` on GET requests as an optional `AgreementStatus` enum while continuing to prohibit manual status overrides on POST/PUT mutations.

6. **`app/Modules/VehicleRental/Http/Controllers/AgreementController.php`**:
   - Extracted optional `status` from `AgreementRequest` and passed it to `AgreementService::list()`.

7. **`app/Modules/VehicleRental/Http/Resources/AgreementResource.php`**:
   - Exposed `successor_agreement` (`id`, `reference`, `status`) to provide frontend clients full forward lineage visibility.

8. **`app/Modules/VehicleRental/Tests/AgreementTest.php`**:
   - Added test `test_agreements_can_be_filtered_by_status`.
   - Added test `test_agreement_delete_is_strictly_prohibited_to_preserve_legal_history`.
   - Added test `test_terms_accept_numeric_int_and_float_values`.
   - Added test `test_owner_agreement_exposes_vehicle_uses_relation`.
   - Added test `test_agreement_resource_exposes_successor_agreement_lineage`.

### Frontend

1. **`resources/js/modules/vehicle-rental/agreements.ts`**:
   - Updated `Agreement` interface with `successor_agreement?: { id: number; reference: string; status: AgreementStatus } | null`.

2. **`resources/js/modules/vehicle-rental/agreementApi.ts`**:
   - Updated `listAgreements` to accept optional `status?: AgreementStatus | ''`.

3. **`resources/js/modules/vehicle-rental/AgreementsPage.tsx`**:
   - Initialized `search`, `query`, and `statusFilter` from `window.location.search`, resolving query loss during navigation from the Vehicle Use register.
   - Added `Select` dropdown for status filtering (`All statuses`, `Draft`, `Active`, `Closed`) next to search input.
   - Updated review action bar: if an active agreement already has a successor draft, replaced "Create successor" with an informative status badge (`Successor in progress (draft): {reference}`).
   - Added successor reference note to agreement details.

4. **`resources/js/modules/vehicle-rental/AgreementEditor.tsx`**:
   - Added `max={endsOn || undefined}` to `Start date` input.

5. **`resources/js/modules/vehicle-rental/AgreementSuccessorForm.tsx`**:
   - Added `max={endsOn || undefined}` to `Effective start date` input.

6. **`resources/js/modules/vehicle-rental/AgreementsPage.test.tsx`**:
   - Added test for status filtering combobox interaction.
   - Added test for successor status badge and suppression of duplicate successor button.

## Verification

- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **180 passed (1,628 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (109 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production build in 9.17s**.
