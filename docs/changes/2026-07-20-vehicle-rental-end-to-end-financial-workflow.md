# Vehicle Rental end-to-end financial workflow

## Business source

All uploaded Vehicle Rental videos remain the business source of truth. The user-facing workflow is intentionally kept at the demonstrated practical level:

```text
Owner / Supplier Agreement, when the vehicle is owner supplied
→ Customer Agreement
→ Select Vehicle
→ Daily Running Chart
→ Customer Invoice
→ Owner Payable Voucher / Owner Settlement
→ Customer Receipt
→ Owner / Supplier Payment
→ Reports
```

One finalized physical Running Chart remains the common operational evidence. Customer billing uses Customer Agreement rates, while owner settlement uses Owner Agreement rates. The two financial sides remain independent, and one side does not block the other.

## Implemented

- Added separate Customer Invoice and Owner Settlement workspaces.
- Added calculation-to-financial-document creation through the existing Invoice lifecycle.
- Customer calculations create outbound Rental invoices.
- Owner calculations create inbound Rental invoices presented as Owner Payable Vouchers.
- Tax snapshots are calculated by the Tax module.
- Finance posting plans are created using named Rental posting profiles and semantic account roles.
- Financial documents are approved and posted atomically with Tax and Finance posting.
- Added Customer Receipt and Owner Payment handoff pages using the existing Payment module and `invoice_id` prefill.
- Added derived Vehicle Rental reporting for finalized usage, customer revenue, owner cost, outstanding balances, and gross margin before tax.
- Split primary agreement navigation into Owner / Supplier Agreements and Customer Agreements while reusing one agreement form implementation.
- Kept vehicle selection contextual to an active agreement.
- Removed technical Assignments and Calculations entries from primary navigation. Their internal operational/audit routes remain available for support and correction workflows.
- Restored governed Rental invoice posting and reversal while keeping historical Vehicle Finance invoices retired.
- Reversed, cancelled, and void financial documents release generic Invoice source capacity for corrected replacement documents.

## Module ownership

- Vehicle Rental owns agreements, rates, assignments, custody, Running Charts, calculation snapshots, and same-side source consumption.
- Invoice owns the financial-document lifecycle, balances, source allocations, posting-plan storage, and reversal coordination.
- Payment owns receipts, owner payments, payment methods, cheques, allocations, posting, refunds, and reversals.
- Tax owns tax calculation and immutable tax snapshots.
- Finance owns accounts, semantic account roles, posting profiles, journals, accounting-period enforcement, ledger, and reversal.

Vehicle Rental does not directly mutate records owned by Customer, Supplier, Vehicle, HR, Invoice, Payment, Tax, or Finance.

## Relationship decision

No Rental-specific calculation-to-invoice link table or bidirectional foreign-key relationship was introduced.

The existing generic `invoice_sources` and `invoice_source_lines` records remain the single source of truth for:

- Rental calculation → Customer Invoice
- Rental calculation → Owner Payable Voucher
- calculation line → invoice line allocation
- idempotent financial-document lookup
- reversal and corrected replacement-document capacity

This avoids redundant relationships, duplicated synchronization logic, circular ownership, and an unnecessary schema migration.

## Finance provisioning

Finance now provisions named defaults for fresh or explicitly reseeded tenants:

- Rental Revenue account and semantic role
- Rental Expense account and semantic role
- Customer Deposits account
- Customer Rental Invoice posting profile
- Owner Rental Payable posting profile
- Rental Security Deposit posting profile

Environment-specific or tenant-specific overrides remain Finance-owned configuration. Vehicle Rental contains no raw GL account IDs or account-code literals.

## Financial correction policy

Posted Customer Invoices and Owner Payable Vouchers are immutable. Correction follows the governed Invoice reversal lifecycle. A Rental calculation cannot be cancelled while a live financial document exists. After an unsettled document is reversed, the calculation can create a corrected replacement document without the reversed source allocation blocking it.

## Permissions

- Rental calculation viewing also protects derived Rental reports.
- Rental calculation management protects billing-period preparation, owner-settlement preparation, and calculation cancellation.
- `vehicle_rental.financial_documents.manage` separately protects Customer Invoice and Owner Payable Voucher creation/posting.
- Invoice and Payment links are shown only when the user has the corresponding owner-module permission.

## Verification commands

```bash
git diff --check
php artisan test --filter=RentalFinancialWorkflowContractTest
php artisan test --filter=VehicleRentalPermissionBoundaryTest
php artisan test --filter=FinanceSeederTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql
npm run typecheck
npm run lint
npx vitest run resources/js/modules/vehicle-rental/rentalFinancialWorkflow.test.ts
npm run test
npm run build
```

Runtime suites must be executed in a local environment with Composer/NPM dependencies and the disposable MySQL test database configured. GitHub Actions are not required or used.
