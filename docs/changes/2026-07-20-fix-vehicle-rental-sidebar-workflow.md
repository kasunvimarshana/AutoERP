# Fix Vehicle Rental sidebar workflow

## Trigger

The Vehicle Rental sidebar exposed agreements, Running Charts, financial documents, settlements, and reports, but hid the assignment lifecycle page required to record handover, return, replacement, and cancellation.

Users could select a vehicle from an active agreement, but could not discover the next required operational step before creating a Running Chart.

The existing labels also exposed implementation-oriented or ambiguous terminology:

- `Owner / Supplier Agreements` was long and truncated in the sidebar.
- `Owner Settlements` did not name the actual Owner Payable Voucher document created by the workflow.

## Root cause

`/vehicle-rental/assignments` was classified as an internal route even though it owns normal operational actions required by the user workflow.

## Fix

The primary Vehicle Rental sidebar now follows the practical workflow:

```text
Overview
Owner Agreements
Customer Agreements
Vehicle Operations
Daily Running Charts
Customer Invoices
Owner Payable Vouchers
Customer Receipts
Owner Payments
Reports
```

- Expose `/vehicle-rental/assignments` as `Vehicle Operations`.
- Protect it with the existing `vehicle_rental.assignments.view` permission.
- Keep the technical combined-agreements and calculation-audit routes internal.
- Rename only navigation labels; route identities and backend contracts remain unchanged.
- Add focused navigation and permission-filter regression coverage.

## Scope and integrity

- Frontend navigation ownership only.
- No schema, relationship, API, backend lifecycle, calculation, Invoice, Payment, Tax, Finance, or Reporting behavior changed.
- Existing route entitlements and granular permissions remain authoritative.
- No placeholder links were added for unimplemented debit notes, credit notes, deposits, refunds, fuel deductions, or repair deductions.

## Verification

```bash
npx vitest run resources/js/modules/vehicle-rental/vehicleRentalFrontendEntry.test.ts
npm run typecheck
npm run lint
npm run test
npm run build
```

Paid tools and GitHub Actions are not used.
