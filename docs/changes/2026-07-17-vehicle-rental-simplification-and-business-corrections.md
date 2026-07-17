# Vehicle Rental simplification and business corrections

Date: 2026-07-17
Branch: `agent/vehicle-rental-simplification`
Base: `worktree-0.0.8` at `16c4fad08456b0a9578987754a97f5f1e6c380cc`

## Purpose

Correct the Vehicle Rental workflows that were confirmed by the legacy-video/current-code comparison to be misleading, unnecessarily duplicated, or financially wrong, while preserving the valid lessee/lessor, allocation, Running Chart, custody, rate-version, calculation-source, Invoice, Payment, Tax, and Finance boundaries.

## Implemented

### Navigation ownership

- Removed the duplicate page-level Vehicle Rental navigation.
- Kept the application sidebar as the single navigation source.
- Removed the redundant generic `Agreements` menu entry from the composed tenant workspace.
- Preserved the separate Lessee Agreements and Lessor Agreements workspaces because they represent independent revenue and cost contracts.

### Reservation promise

- A category/general request may remain Draft or Pending.
- A reservation cannot transition to Confirmed until a specific available vehicle is selected.
- The lessee-agreement conversion action is visible only for Confirmed reservations.
- No unimplemented category-capacity guarantee was invented.

### Replacement workflow

- Removed the `Continue billing period / Split billing period` selector because the calculation engine did not implement that financial choice.
- The request rejects the removed field instead of silently accepting it.
- Replacement is now recorded as one atomic Completed event rather than persisting a fake Draft state that was completed inside the same transaction.
- The current agreement billing period remains unchanged through a named internal policy until stakeholders approve a replacement charging rule.
- Old return, new allocation, and new handover remain inside one database transaction.

### Rate units and Tax treatment

- Rate metadata now exposes the supported units for every component.
- Base Rental and Driver Salary are no longer forced to monthly units by the UI.
- Required and non-zero rate components require an explicit supported unit.
- Every active component requires an explicit Taxable or Non-taxable selection.
- The backend validates the same component-unit catalogue as the frontend.
- The calculation engine now excludes non-taxable component lines from Tax calculation.
- Tax treatment is frozen in the calculation rule snapshot.
- The temporary calculation-only Tax flag is removed before persistence.
- The dead Withholding Tax rate component is not exposed and cannot be used for a new rate; withholding remains Tax-owned.

### Running Chart commercial facts

- Customer and owner commercial facts are displayed as read-only derivations by default.
- Editing is available only through an explicit `Record commercial variance` action.
- Variance editing requires a variance reason and preserves existing range, version, approval, and reversal validations.
- Separate commercial approvals remain because maker-checker/segregation policy has not been approved for automatic combined approval.

## Relationship review

No database relationship was removed in this batch.

Preserved as valid:

- Lessee agreement versus lessor agreement.
- Agreement versus effective-dated vehicle allocation.
- Customer allocation to owner source allocation.
- One physical Running Chart to independent Revenue and Cost contexts/facts.
- Rate-version and calculation-source lineage.
- Custody-event history.
- Central Invoice, Payment, Tax, and Finance ownership.

The replacement lineage and Rental expense source/target schemas were not changed because removing one authority requires a released-schema migration and a complete historical-data migration. That work is not safe without an approved persistent-schema upgrade plan.

## Intentionally blocked decisions

The following were not guessed or implemented:

- Replacement charging or period-splitting formula.
- Category-capacity reservation guarantee.
- Downtime and off-road deductions.
- Included-KM pooling and garage-mileage billing.
- Fuel, repair, damage, accident, and insurance responsibility/source selection.
- Deposit application and forfeiture priority.
- Combined physical/customer/owner approval policy.
- Vehicle Finance/Asset Finance product scope and migration out of Vehicle Rental.
- Legal-document availability requirements and future workshop/off-road timelines.
- General employee-reimbursement ownership.
- Tax manual-override authority.
- Persistent replacement-lineage and expense relationship migrations.

## Verification gates

Required before merge:

```bash
php artisan test --filter=RentalReservationConfirmationTest
php artisan test --filter=RentalReplacementWorkflowContractTest
php artisan test --filter=RentalRateComponentCatalogTest
php artisan test --filter=RentalCalculationTaxTreatmentTest
php artisan test
composer test:mysql

npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build

git diff --check
git status --short
git rev-parse HEAD
```

Do not merge if any gate fails. No production database should be refreshed or rebuilt to validate this batch.
