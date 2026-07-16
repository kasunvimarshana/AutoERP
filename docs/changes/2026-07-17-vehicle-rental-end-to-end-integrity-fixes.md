# Vehicle Rental end-to-end integrity fixes

**Date:** 2026-07-17  
**Source branch:** `worktree-0.0.8` at `77105cdb2435294a856be4dadbe2d13812d1f4f7`

## Why

A video-derived Vehicle Rental rulebook was compared with the current authoritative source. Legacy screen weaknesses were not copied into AutoERP. This change addresses only confirmed current defects and clearly justified ownership/maintainability issues; commercial rules that remain unconfirmed are intentionally not guessed.

## Changes

### Rental expense reversal integrity

- Added a source-owner guard before reversing a Rental expense.
- Expense reversal now requires a reason and is rejected while any expense allocation is consumed by an approved Rental calculation.
- The required correction order is preserved: settlement/payment reversal, Invoice/payable reversal, Rental calculation reversal, then Rental expense reversal.
- Added a behavioral feature test covering rejection and successful reversal after downstream release.

### Vehicle Service and Rental availability

- Vehicle Service now projects an in-progress workshop job through the Vehicle-owned `VehicleStatusService`.
- An Active vehicle becomes `UnderService` when workshop work starts.
- The vehicle returns to Active only after the last in-progress workshop job completes or is cancelled.
- A Rented or otherwise unavailable vehicle cannot start an in-progress service job.
- Rental availability now treats `UnderService` as unavailable and returns an explicit business error.
- Added a cross-module behavioral feature test for the complete status/availability lifecycle.

### Agreement and rate draft lifecycle

- New agreements keep the initial rate version in Draft unless a caller explicitly requests immediate activation.
- Draft agreement and Draft rate changes are saved atomically with independent expected-version checks.
- Draft rate fields inherited from the agreement remain synchronized without overwriting explicit rate overrides.
- Agreement activation requires exactly one reviewed Draft rate and activates/snapshots it in the same transaction.
- Structural fields remain editable while only Draft dependencies exist; committed rates or allocations lock the structure.
- Pending untouched security-deposit identity follows allowed Draft customer/currency changes; deposit activity prevents unsafe identity changes.
- The frontend now presents a clear create/edit Draft, review, then activate workflow.

### Explicit commercial policy and metadata ownership

- Removed unapproved application-wide Vehicle Rental commercial defaults.
- Agreement legal context, proration rule, excess-KM method and rate components must be selected explicitly.
- Added a backend-owned Rental rate-component catalog and exposed it through metadata.
- Removed the duplicated frontend rate code/unit map; the agreement UI now renders the authoritative metadata definition.

### Vehicle Finance semantics

- Added typed enums for finance interest method and installment frequency.
- Finance terms that affect money are now explicit inputs rather than service fallbacks.
- Added the `vehicle_finance` Invoice type.
- Vehicle-finance installment payables no longer use the Rental Invoice type, preventing Rental revenue/cost reporting contamination.
- The existing finance capability remains in place because moving the subdomain/tables requires an explicit product scope decision; no speculative schema move was made.

## Relationship review

- No valid Rental relationship was removed.
- No circular Vehicle-to-Vehicle-Service model relationship was introduced. Vehicle Service writes the canonical Vehicle status through the Vehicle service boundary; Rental only consumes Vehicle status.
- Customer allocations continue to link owner-supplied vehicles through the covering owner allocation because that relationship preserves independent lessee and lessor commercial contexts.
- Agreement, rate, allocation, custody, Running Chart, calculation and Invoice/Payment relationships remain separate; no denormalized compatibility relationship was added.
- Vehicle Finance classification was separated at the Invoice semantic boundary without moving tables blindly.

## Decisions deliberately not implemented

The videos/current evidence do not approve exact rules for partial-period proration choice, category reservation capacity guarantees, replacement charging, downtime, included-KM pooling, garage mileage, fuel/damage responsibility, deposit priority, driver split, early termination, holiday overtime, foreign exchange, Tax ordering, credit enforcement or splitting one Running Chart across agreements. These remain stakeholder decisions and must not be encoded as hidden defaults.

## Verification added

- `RentalExpenseReversalIntegrityTest`
- `VehicleServiceRentalAvailabilityIntegrationTest`
- `RentalAgreementDraftLifecycleTest`
- `RentalRateComponentCatalogTest`
- `VehicleFinanceInvoiceClassificationTest`
- `VehicleFinanceOptionEnumTest`
- Free repository-owned GitHub Actions workflow for full SQLite backend, full MySQL backend, frontend typecheck, lint, tests and production build.

The PR is merged only after all workflow jobs pass.

## Deployment notes

- No database migration is introduced by this change.
- Existing string-backed Invoice records remain valid; the new `vehicle_finance` value applies to new finance-installment payables.
- Existing Rental agreements/rates are not rewritten.
