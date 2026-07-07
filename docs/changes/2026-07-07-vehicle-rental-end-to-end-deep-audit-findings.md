# Vehicle Rental End-to-End Deep Audit Findings

Date: 2026-07-07

## Scope

Audited the current Vehicle Rental flow end to end after the latest lessee, lessor, allocation, running-chart, and agreement hardening records:

- agreement creation and inline rate activation
- vehicle allocation, ownership/source selection, custody activation/return, and replacement handoff
- running-chart physical usage, commercial usage facts, rate-version boundaries, and calculation handoff
- rental billing, invoice/payable generation, expense recovery/deduction, and frontend guided relationship entry
- current Vehicle Rental migrations, resources, authorization boundaries, and focused contract coverage

No runtime code was changed in this audit. This record captures remaining issues and confirmed guardrails for a future implementation pass.

## Findings

### Driver salary and monthly billing rates can calculate with the wrong quantity

The agreement create UI creates `driver_salary` as a monthly rate component by default in `resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx`. Billing calculation then maps `RentalRateComponentCode::DriverSalary` through `quantityByUnit()` in `app/Modules/VehicleRental/Services/RentalCalculationService.php`.

`quantityByUnit()` converts minutes to hours only when the unit is `hour`; every other unit receives raw minutes. With the UI default `driver_salary/month`, an 8-hour day produces a quantity of 480, so a monthly driver salary rate can be multiplied by worked minutes instead of being charged once or prorated by the billing period.

The same calculation path stores billing cycle, billing basis, proration rule, included hours, and weekday/weekend included-minute configuration, but the active calculation logic does not apply those fields when deriving base or driver quantities. Monthly base rental also returns `1.000000` for any selected period, while the billing page allows arbitrary period start/end dates.

Impact: customer invoices and owner payables can be materially overstated or understated when users configure normal monthly driver salary/base rates through the visible UI.

Recommended fix: make rate-component quantity calculation explicit per component and unit. Either constrain driver salary to supported time units in the UI/API or calculate fixed/month/week/day quantities using the rate version billing basis and proration rule. Add runtime calculation tests with a monthly driver salary component and a short billing period.

### Rental calculation mutates rental expense lifecycle state outside the expense owner

`RentalCalculationService::syncExpenseAllocationStatuses()` directly updates `rental_expense_allocations` and `rental_expenses` after calculation approval/reversal. Allocation row versions are bumped, but the parent `rental_expenses` status is changed between `approved` and `allocated` without incrementing `rental_expenses.row_version`, without locking the expense rows through `RentalExpenseService`, and without writing rental status history.

Impact: an expense can change lifecycle state behind the UI's loaded row version, and the normal expense transition audit trail misses the approved-to-allocated or allocated-to-approved movement. This weakens the project's conflict-aware write and historical-status contracts.

Recommended fix: move expense consumption/reopen behavior into the expense-owned service boundary, or introduce an explicit expense lifecycle method used by calculation approval/reversal. It should lock affected expense rows, apply a versioned state transition, and record status history consistently.

### Rental expense recovery/deduction UI allows incomplete or mismatched relationship submissions

The rental expense backend correctly requires customer recoveries and owner deductions to target a matching rental agreement and party. The frontend, however, only disables Save based on vehicle, currency, amount, and party selection. It does not require `targetAgreement` before submitting a `customer_recovery` or `owner_deduction`, and it asks users to select a customer/supplier independently from the selected agreement.

Impact: users can complete the visible form and hit backend validation errors for a missing target agreement or party/agreement mismatch. The backend protects data integrity, but the UI is not yet a fully guided relationship workflow for this action.

Recommended fix: make the target agreement the source of truth for recoveries/deductions, filter by agreement kind, derive or lock the customer/supplier from the selected agreement, and disable Save until the backend-required relationship context is complete.

### Calculation aggregate child rows are updated without their own versions moving

Calculation transitions update calculation line statuses and billing-period final/reopened state while only the calculation run row version is advanced by the transition wrapper. `rental_calculation_lines` and `rental_billing_periods` both have `row_version` columns, but those versions are not incremented during these transition side effects.

Impact: this is currently less user-visible than the expense lifecycle issue because lines and periods are not edited independently in the UI, but it leaves versioned rows with stale versions after state changes and weakens future conflict-aware extensions.

Recommended fix: treat the calculation run, billing period, lines, and sources as one explicit aggregate transition and bump versions consistently for every row whose state is changed.

## Confirmed Guardrails

- Lessee and lessor agreement creation use explicit agreement kinds and party-side validation.
- Lessor agreements reject customer security deposits at request/service/schema boundaries.
- Agreement, allocation, custody, running-chart, usage-fact, calculation, deposit, and finance mutating HTTP commands carry expected row versions where exposed.
- Running-chart creation requires active customer allocations, active source allocations when present, active driver assignments for with-driver rentals, and single-rate-version usage periods.
- Reversed running-chart entries no longer block exact re-entry because fingerprint sequence is lifecycle-aware.
- Usage and calculation API resources return structured related objects for reviewed relationships instead of exposing raw foreign keys as the primary UI contract.
- Vehicle Rental module migrations reviewed in this pass are explicit create migrations without dynamic loops or late `Schema::table()` patch patterns.
- Route authorization is present across the audited Vehicle Rental controllers and separates operational, financial, approval, and document-creation permissions.

## Verification

- Read the latest `/docs/changes` records before reviewing code.
- Reviewed Vehicle Rental services, controllers, requests, resources, migrations, frontend pages/components, and focused tests.
- Checked the referenced video files exist and read basic Windows shell metadata. Frame-level inspection was not performed because `ffmpeg`, `ffprobe`, and Python video tooling are unavailable in this environment.
- `php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npm run typecheck -- --pretty false`

Result: focused backend tests passed with 19 tests and 377 assertions; frontend typecheck passed.
