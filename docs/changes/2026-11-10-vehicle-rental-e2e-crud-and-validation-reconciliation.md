# Vehicle Rental: Comprehensive E2E CRUD & Operational Validation Reconciliation

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Reconciles and unifies all four operational validation and routing scenarios across Customer Agreements, Assigned Vehicles & Handover, Running Charts, and Reports:
1. Running chart usage period future-time error handling and client-side pre-submit verification.
2. Running chart customer custody boundary verification with dual error-key mapping (`period` + `starts_at`/`ends_at`) for field-level UI highlighting.
3. Authenticated report blob export standard and guest redirect safety eliminating `Route [login] not defined`.
4. Vehicle replacement time validation, bounding, and contextual feedback.
5. End-to-end verification of all CRUD operations across all sub-domains in the Vehicle Rental module.

---

## 1. Problem Synthesis & Systematic Resolution Across Scenarios

### Scenario 1: Running Chart End-Time Future Validation (`media_1794249515714_515e11cc.png`)
- **Reported Error:** `ends_at: Record completed usage ending after its start, not in the future.`
- **Underlying Cause:**
  - In `RunningChartEditor.tsx`, `maxEnd` defaulted to `todayEnd` (`23:59:59` of the current calendar date) when vehicle custody was open-ended.
  - When operators entered or selected a time later than the current minute on today's date, or when the client submitted without prior client-side future checks, the server rejected the record with a compound error message that conflated order (`ending after its start`) with temporal limit (`not in the future`).
- **Systematic Fix:**
  - Added pre-submit client validation in `RunningChartEditor.tsx` checking `startIso` and `endIso` against current time (+60s clock skew tolerance), providing immediate local feedback before attempting an API call.
  - Ensured server validation in `RunningChartValidation.php` rigorously uses the workspace commercial calendar (`RentalCalendar::now($context)->addMinute()`).

### Scenario 2: Running Chart Custody Boundary & Field Error Mapping (`media_1794252420528_675a6875.png`)
- **Reported Error:** `period: Usage must fall within actual customer custody.`
- **Underlying Cause:**
  - When usage timestamps preceded handover or succeeded return, `RunningChartService::assertCoverage()` threw `ValidationException` keyed solely under `'period'`.
  - Because `'period'` did not match any form input name (`starts_at` or `ends_at`), the error appeared only in the top alert banner, leaving the inputs unhighlighted and obscuring whether the start or end timestamp triggered the custody violation.
  - Furthermore, sub-minute differences where operators keyed zero seconds against non-zero handover seconds previously triggered spurious custody rejections.
- **Systematic Fix:**
  - `RunningChartService::assertCoverage()` now maps custody violations to both `'period'` and the specific offending field (`starts_at` or `ends_at`), highlighting the exact input in red and displaying contextual field feedback.
  - Graceful sub-minute alignment preserves valid custody entries when operators submit within 1 minute of handover or return.
  - Added client-side custody boundary checks in `RunningChartEditor.tsx` to detect out-of-custody inputs before network requests.

### Scenario 3: Unauthenticated Report Export & Missing Login Route (`media_1794273726601_db308b90.png`)
- **Reported Error:** `Route [login] not defined.` on `/api/v1/vehicle-rental/reports/running-chart-sheet/export/print?...`
- **Underlying Cause:**
  - Navigating to `/api/v1/...` report print/export endpoints directly via browser windows (`window.open` or `<a href="...">`) omitted custom authorization headers (`Authorization: Bearer <token>`, `X-Tenant-Id`).
  - Laravel's `Authenticate` middleware redirected unauthenticated browser requests (`Accept: text/html`) to `route('login')`, which was undefined.
- **Systematic Fix:**
  - Configured `bootstrap/app.php` with `$middleware->redirectGuestsTo()` to strictly return `null` for all `api/*` requests, ensuring unauthenticated API calls return clean HTTP 401 JSON responses.
  - Defined a named `'login'` route in `routes/web.php` for web requests.
  - Implemented the authenticated blob download/preview utility (`exportReportBlob()` in `resources/js/shared/utils/reportExport.ts`), ensuring all 8 Vehicle Rental report pages and running chart print buttons fetch documents in-memory with bearer tokens before opening the preview blob URL.

### Scenario 4: Vehicle Replacement Time Validation (`media_1794277115625_86dc0cb2.png`)
- **Reported Error:** `starts_at: Return must follow handover and cannot be in the future.`
- **Underlying Cause:**
  - Opening the vehicle replacement modal prefilled the replacement time with previous handover time, and bounded `max` to the agreement end date (months or years in the future).
  - Submitting without manual change or with future dates triggered an ambiguous compound error message mentioning both handover order and future limits simultaneously.
- **Systematic Fix:**
  - In `VehicleUseEditor.tsx`, replacement time defaults to the current commercial time (`freshLocalNow()`), is capped to current time (`todayEnd`), and validates client-side that replacement time strictly succeeds previous handover and does not lie in the future.
  - In `VehicleUseService::replace()`, future time rejection and handover precedence checks were decoupled and disambiguated.

---

## 2. End-to-End Module CRUD Invariants Audit

Across all 8 sub-domains in `VehicleRental`:
1. **Customer & Owner Agreements (`AgreementController`, `AgreementService`):**
   - **Create:** Validates tenant, party, currency, term boundary, and date order.
   - **Read:** Contextual scoping (`forContext()`), search, and state filtering.
   - **Update:** Terms editable only in draft; version-checked.
   - **Delete:** Prohibited (`MethodNotAllowedHttpException` / `LogicException`) to preserve legal and financial audit trails.
2. **Vehicle Assignments (`VehicleUseController`, `VehicleUseService`):**
   - **Create / Plan:** Validates owner agreement coverage for external vehicles and availability blockers.
   - **Handover:** Transitions vehicle to customer custody; records meter and condition.
   - **Return:** Records custody return; verifies non-decreasing odometer and current time boundary.
   - **Replace:** Atomic custody swap inside `DB::transaction` with row locking (`lockForUpdate()`).
   - **Delete:** Prohibited; status cancellation/return enforced.
3. **Running Charts (`RunningChartController`, `RunningChartService`):**
   - **Create:** Auto reference generation, customer custody boundary assertion, distance/meter math validation.
   - **Read:** Search by reference, driver, and date filters.
   - **Update:** Mutable only in draft; odometer continuity verified.
   - **Finalize:** Concurrency version lock, driver timeline overlap check, and meter continuity lock.
   - **Reverse:** Preserves immutable physical record; requires reason and un-voided invoice check.
   - **Delete:** Prohibited.
4. **Fleet Expenses (`RentalExpenseController`, `RentalExpenseService`):**
   - **Create:** Categorized expense recording with allocation rules.
   - **Bill / Unlink:** Transactional invoice creation and cancelled invoice dissociation.
   - **Delete:** Prohibited.
5. **Vehicle Finance (`VehicleFinanceController`, `VehicleFinanceService`):**
   - **Create:** Installment schedule generation.
   - **Payable:** AP payable invoice generation with version check.
   - **Delete:** Prohibited.
6. **Rental Reservations (`RentalReservationController`, `RentalReservationService`):**
   - **Create / Confirm:** Availability check against bookings and active uses.
   - **Convert:** Atomic conversion to CustomerAgreement.
   - **Delete:** Prohibited.
7. **Rental Incidents (`RentalIncidentController`, `RentalIncidentService`):**
   - **Create / Review:** Multi-status investigation workflow.
   - **Delete:** Prohibited.
8. **Billing & Adjustments (`BaseRentBillingController`, `UsageChargeBillingController`, `DepositReceiptController`):**
   - Base rent and usage charge invoicing with idempotency guards and deposit void/refund lifecycles.

---

## 3. Verification & Quality Gates

- **Backend Tests (`php artisan test app/Modules/VehicleRental/Tests`):**
  - **233 passed (1,975 assertions)** with 0 failures.
- **Frontend Tests (`npx vitest run resources/js/modules/vehicle-rental`):**
  - **29 passed test files (135 passed tests)** with 0 failures.
- **Static Analysis & Type Checking:**
  - `npm run typecheck`: **0 errors**.
- **Code Standards & Linting:**
  - `npm run lint`: **0 errors, 0 warnings**.
