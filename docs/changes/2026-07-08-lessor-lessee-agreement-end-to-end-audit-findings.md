# Lessor and Lessee Agreement End-to-End Audit Findings

Date: 2026-07-08

Scope: End-to-end audit of customer-rental (lessee-facing revenue) and owner-supply (lessor-facing cost) agreement flows, including agreement creation/detail/list, allocation, running-chart usage, billing calculation, invoice creation, deposits, schema constraints, frontend guidance, and contract coverage.

Change type: Audit record only. No runtime code was changed.

## Context Reviewed

- Recent append-only records in `docs/changes`, especially the 2026-07-07 and 2026-07-08 vehicle-rental audit and fix records.
- Backend agreement, allocation, rate, usage, calculation, invoice, and deposit services and requests.
- Frontend lessee and lessor agreement pages, billing page, running-chart page, allocation page, deposit page, lookups, routes, and shared API types.
- Existing vehicle-rental integrity and end-to-end contract tests.

## Findings

### 1. Deposit forfeiture records a rental-side link but does not settle finance state

Severity: High

`RentalDepositService::applyToInvoice()` validates the invoice and creates a `PaymentAllocationData` through `PaymentAllocationService`, so invoice balance and payment unapplied balance are updated through the payment module. `RentalDepositService::forfeit()` validates the invoice but only creates a `RentalDepositLinkType::Forfeiture` link and syncs the deposit requirement.

Evidence:

- `app/Modules/VehicleRental/Services/RentalDepositService.php:96` applies deposits through payment allocation.
- `app/Modules/VehicleRental/Services/RentalDepositService.php:134` calls the payment allocation service.
- `app/Modules/VehicleRental/Services/RentalDepositService.php:202` handles forfeiture.
- `app/Modules/VehicleRental/Services/RentalDepositService.php:224` records only the forfeiture link.
- `resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx:316` exposes "Forfeit against invoice".
- `resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx:392` requires a receipt/payment for apply/refund, but not for forfeit.

Impact: A forfeiture can reduce the rental deposit balance while the selected invoice remains unpaid and the original deposit payment remains unapplied in the finance modules. This breaks financial integrity and creates inconsistent customer-facing balances.

Recommended fix: Implement forfeiture in the financial owner path. Either allocate the original deposit payment to the invoice with payment/version checks, or create a proper invoice adjustment/journal/source document for forfeiture. The rental module should not claim financial settlement without updating the payment/invoice source of truth.

### 2. Same-day billing periods are rejected even though calculation logic is inclusive

Severity: Medium

The billing UI allows `periodStart === periodEnd`, and the calculation service fetches usage with inclusive `whereDate >= start` and `<= end` logic. The backend request and service reject same-day periods.

Evidence:

- `app/Modules/VehicleRental/Http/Requests/CalculateRentalRequest.php:19` requires `period_end` to be after `period_start`.
- `app/Modules/VehicleRental/Services/RentalCalculationService.php:73` rejects non-greater end dates.
- `app/Modules/VehicleRental/Services/RentalCalculationService.php:87` and `:88` query usage inclusively.
- `resources/js/modules/vehicle-rental/pages/RentalBillingPage.tsx:212` allows the end date to equal the start date.
- `resources/js/modules/vehicle-rental/pages/RentalBillingPage.tsx:220` disables calculation only when start is greater than end.

Impact: One-day rentals and first-day-of-month billing fail for both lessee revenue and lessor cost flows even though the UI and calculation model imply inclusive date ranges.

Recommended fix: Align validation with the calculation model by allowing `after_or_equal` and updating the service guard/message to accept equal dates.

### 3. Agreement version checks on child writes do not advance the aggregate version

Severity: Medium

Child commands require `expected_agreement_version`, but successful child writes do not advance the agreement `row_version`. Allocation creation, rate draft creation, and calculation creation check the parent version while only mutating child records.

Evidence:

- `app/Modules/VehicleRental/Services/RentalAllocationService.php:42` requires agreement version for allocation creation.
- `app/Modules/VehicleRental/Services/RentalAllocationService.php:150` bumps only allocation version.
- `app/Modules/VehicleRental/Services/RentalRateVersionService.php:34` checks agreement version for rate draft creation.
- `app/Modules/VehicleRental/Services/RentalRateVersionService.php:116` creates rate components without advancing the agreement version.
- `app/Modules/VehicleRental/Services/RentalCalculationService.php:68` checks agreement version for calculation.
- Existing tests assert that versions are sent and checked, but not that the aggregate version changes after child mutations.

Impact: A stale agreement detail screen can pass child-write checks after another user adds a rate, allocation, or calculation, because the parent token has not changed. This weakens the concurrency invariant that writes are version-checked and conflict-aware.

Recommended fix: Define the aggregate boundary explicitly. If agreement `row_version` is the aggregate concurrency token, bump it atomically for child writes that depend on agreement state. If not, remove misleading parent checks and require the actual child/source versions that own the conflict.

### 4. Global rate component uniqueness is ambiguous with nullable category

Severity: Medium

The rate component table allows `vehicle_category_id` to be nullable and defines uniqueness across `rate_version_id`, `vehicle_category_id`, and `component_code`. SQL unique indexes allow multiple `NULL` values, so duplicate global components can be inserted for the same rate version and component code. The service does not reject duplicate components before inserting them.

Evidence:

- `app/Modules/VehicleRental/Database/Migrations/2026_06_12_200005_create_rental_agreement_rate_components_table.php:20` allows nullable vehicle category.
- `app/Modules/VehicleRental/Database/Migrations/2026_06_12_200005_create_rental_agreement_rate_components_table.php:36` defines the nullable composite unique index.
- `app/Modules/VehicleRental/Services/RentalRateVersionService.php:64` validates component/unit compatibility, not duplicate component keys.
- `app/Modules/VehicleRental/Services/RentalRateVersionService.php:116` inserts submitted components.
- `app/Modules/VehicleRental/Services/RentalCalculationService.php:582` groups by component code and selects a matching category or first global component.

Impact: Duplicate global rates such as two `base_rental` components can exist for one rate version. Calculation then chooses one by ordering instead of rejecting ambiguous pricing.

Recommended fix: Validate duplicate `(component_code, vehicle_category_id)` entries in `RentalRateVersionService` before insert. Consider a portable, explicit schema strategy for null-safe uniqueness if database-level enforcement is required.

### 5. Running-chart event unit is free text and ignored by calculation

Severity: Low to Medium

The running-chart form stores a free-text event unit, but calculation sums event quantity by event type and prices it using the rate component unit. The entered unit does not affect calculation.

Evidence:

- `app/Modules/VehicleRental/Http/Requests/StoreRentalUsageRequest.php:41` accepts nullable string units.
- `resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx:767` renders a free-text unit input.
- `resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx:771` stores whatever the user types.
- `app/Modules/VehicleRental/Services/RentalCalculationService.php:1036` sums event quantities by event type only.

Impact: Users can enter units that conflict with the billing unit, such as parking quantity in litres, while the calculator prices the quantity as the configured rate component unit. That creates confusion and weakens guided data entry.

Recommended fix: Derive event units from event type or active rate configuration, present them as controlled/read-only values in the UI, and validate them in the backend if stored.

## Already Verified As Fixed Or Not Re-Raised

- Deposit requirement `agreement_kind` migration now uses a compatible string column for the agreement composite foreign key.
- Running-chart usage now enforces active driver assignments before recording driver-based usage.
- `pass` usage events are present in enums and calculation mapping.
- Agreement detail pages hide actions that do not belong to the selected lessee or lessor route mode.

## Verification

- Read recent records in `docs/changes` before auditing.
- Reviewed the relevant backend services, requests, migrations, resources, frontend pages, API types, route registrations, and contract tests.
- No application code was changed by this audit.
