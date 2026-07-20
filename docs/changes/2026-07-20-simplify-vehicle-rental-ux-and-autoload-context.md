# Simplify Vehicle Rental UX and autoload operational context

## Trigger

The Vehicle Rental backend already protected agreement, assignment, running-chart, calculation, and financial-document integrity. The frontend still exposed repeated technical choices and allowed inputs that the backend would later reject. This made the normal workflow more complex than the video source of truth.

## User-facing workflow

```text
Agreement
→ Select vehicle
→ Handover
→ Daily running chart
→ Customer billing / Owner settlement
→ Receipt / Payment
```

The UI should ask for observed business facts and carry forward known agreement, vehicle, driver, date, odometer, and financial context. Backend lifecycle and relationship identifiers remain hidden implementation details.

## Changes

- Rental agreement entry now defaults execution and start dates to the business date.
- Selecting a customer or owner carries its default currency into a new agreement while leaving the currency editable.
- Unsupported fixed `Other` rates are no longer offered for new agreements.
- Existing unsupported `Other` rows are clearly identified and must be removed before saving.
- The authoritative rate policy now rejects unsupported fixed `Other` rates before activation or calculation.
- Agreement lookups now carry billing basis, date bounds, currency, vehicle, driver, owner-agreement, and odometer context needed by downstream forms.
- Monthly billing and owner-settlement preparation now uses one month selector and derives the complete calendar-month period required by the calculation engine.
- Daily running-chart entry now:
  - derives the operational date from the start timestamp;
  - carries assignment, vehicle, customer agreement, owner agreement, driver, and handover odometer context;
  - previews total and commercial kilometres;
  - hides driver overtime and night-out inputs for self-drive assignments;
  - constrains timestamps to the selected assignment period.
- Customer-receipt and owner-payment workspaces now request active outstanding financial documents from the backend before pagination instead of filtering the current page in the browser.
- Internal concurrency row-version values are no longer shown in agreement and vehicle-operation detail dialogs.
- Technical assignment labels were replaced with customer-facing vehicle-operation labels.

## Ownership and integrity

- Rate support remains owned and enforced by the Vehicle Rental rate policy.
- Settlement pagination is fixed in the Vehicle Rental calculation query, not hidden in frontend filtering.
- Payment methods, allocations, cheque handling, posting, and reversal remain owned by the Payment and Finance modules.
- Running-chart totals shown in the UI are previews only; backend decimal calculation and validation remain authoritative.
- No schema changes, historical-record mutation, permission weakening, or compatibility workaround was introduced.

## Deferred decisions

The following require separate backend-owned work and are intentionally not guessed or partially implemented here:

- currency-safe report aggregation and base-currency conversion;
- authoritative foreign-exchange-rate resolution for financial documents;
- owner Non-AC / Front-AC / Dual-AC payable policy confirmed from business evidence;
- agreement-and-period-aware available-vehicle lookup;
- unique owner-source automatic selection;
- calculation preview and report drill-down expansion.

## Verification

Completed without paid tools or GitHub Actions:

```bash
php -l app/Modules/VehicleRental/Http/Requests/ListRentalRequest.php
php -l app/Modules/VehicleRental/Http/Controllers/RentalCalculationController.php
php -l app/Modules/VehicleRental/Services/RentalFinancialDocumentService.php
php -l app/Modules/VehicleRental/Services/Validation/RentalRatePolicy.php
```

The changed TSX files were syntax-transpiled with TypeScript `transpileModule` using React JSX and ESNext module settings. Focused regression coverage was added for the rate policy and the Vehicle Rental UX source contracts.

A full dependency-backed Laravel/Vitest/typecheck/lint/build run was not available in the connector-only environment. The draft pull request must remain unmerged until those local project commands pass.
