# AutoERP Vehicle Rental — End-to-End Redesign Implementation Report

## Source of truth

The module was rebuilt from the workflows identified in:

- `1.mp4`
- `2.mp4`
- `Recording 2026-06-21 132314.mp4`

The legacy Vehicle Rental implementation in `AutoERP-2026051623-refactor-core-modules-clone-86-ui` was removed before the new baseline was created.

## Canonical design

```text
One approved Running Chart
├── Customer revenue
│   → Customer invoice
│   → Receipt allocation
│   → Customer statement
└── Vehicle-owner cost
    → Owner payable
    → Fuel / repair / WHT deductions
    → Owner payment
    → Owner / vehicle statement

Leasing company
→ Vehicle finance agreement
→ Installment schedule
→ Payable
→ Payment
```

Customer billing rates and owner payable rates are independent, effective-dated and immutable after activation.

## Removed legacy implementation

The previous Vehicle Rental module was deleted and replaced, including its:

- 18 legacy migrations;
- models and DTOs;
- controllers and requests;
- services;
- routes and provider;
- seeders;
- frontend pages and API layer;
- legacy tests and old table references.

Removed legacy concepts include:

- `rental_agreement_vehicles`
- `rental_agreement_rate_snapshots`
- `rental_pickup_inspections`
- `rental_return_inspections`
- `rental_agreement_vehicle_links`
- `rental_charge_runs`
- `rental_charge_calculations`
- `rental_charges`
- `rental_invoice_links`
- `rental_payment_links`

## New database baseline

The rebuilt module contains 24 one-table-per-file migrations:

1. `rental_reservations`
2. `rental_agreements`
3. `rental_agreement_terms`
4. `rental_agreement_rate_versions`
5. `rental_agreement_rate_components`
6. `vehicle_finance_agreements`
7. `vehicle_finance_installments`
8. `vehicle_finance_status_histories`
9. `rental_vehicle_allocations`
10. `rental_driver_assignments`
11. `rental_vehicle_replacements`
12. `rental_custody_events`
13. `rental_custody_event_items`
14. `rental_usage_logs`
15. `rental_usage_events`
16. `rental_usage_contexts`
17. `rental_expenses`
18. `rental_expense_allocations`
19. `rental_billing_periods`
20. `rental_calculation_runs`
21. `rental_calculation_lines`
22. `rental_deposit_requirements`
23. `rental_deposit_links`
24. `rental_status_histories`

All module migrations use portable Laravel Schema Builder and have correct reverse dependency order.

## Backend implementation

### Agreements and rates

- Customer rental and owner supply agreements are explicit agreement kinds.
- Customer and supplier counterparties use real foreign keys.
- Agreement terms are normalized.
- Rates use effective-dated immutable versions.
- Rate components support vehicle-category overrides.
- Excess-kilometre methods:
  - period;
  - per hire;
  - per usage log.
- Normal, double and triple overtime are supported.
- Security-deposit requirements can be created with customer agreements.

### Vehicle allocation and custody

- Vehicle availability and allocation overlap checks are backend-enforced.
- Company-owned, owner-supplied and financed vehicle sources are supported.
- Owner-supply allocations can be linked to customer allocations.
- Driver assignments are effective-dated.
- Full custody chain is represented:
  - owner to company;
  - company to customer;
  - customer to company;
  - company to owner;
  - replacement out/in;
  - internal transfer.
- Custody events capture odometer, fuel, condition, damage, people, items and attachments.
- Vehicle replacement closes and opens the required allocations and custody events in one transaction.

### Running Chart

- Running Chart is the operational source of truth.
- Backend validates:
  - allocation period;
  - custody period;
  - driver assignment;
  - time overlap;
  - odometer chronology;
  - vehicle continuity;
  - overtime totals;
  - duplicate submission fingerprint.
- Usage supports:
  - chargeable distance;
  - garage distance;
  - internal distance;
  - working minutes;
  - normal OT;
  - double OT;
  - triple OT;
  - night-outs;
  - parking, toll, waiting, fuel and other events.
- Revenue and cost contexts freeze the applicable agreement, allocation and rate version.

### Calculation and finance sources

- Billing periods are separate per financial side.
- Calculation runs are versioned.
- Calculation lines are the single detailed source for revenue and owner cost.
- Expense allocations support:
  - company cost;
  - customer recovery;
  - owner deduction;
  - employee reimbursement.
- Calculation approval locks source usage and expense allocations.
- Reversal reopens the period and restores sources when no active financial document blocks reversal.
- Aggregate run state derives from the full run rather than one child line.

### Core module integration

Vehicle Rental owns rental-domain rules only. Core modules remain authoritative for shared financial rules.

- Invoice creation: `InvoiceCreationService`
- Invoice source tracing: `invoice_sources`, `invoice_source_lines`
- Payments: `PaymentCreationService`
- Allocations: `PaymentAllocationService`
- Tax calculation: `TaxCalculationService`
- Vehicle state: `VehicleStatusService`
- Attachments: shared polymorphic attachments
- Numbering: `SequenceNumberService`
- Ledger and statements: central Finance module

The redesign does not recreate rental-specific invoice, payment or ledger engines.

### Deposits

- Deposit requirement and currency are stored separately from payments.
- Supported movements:
  - receipt;
  - invoice application;
  - refund;
  - forfeiture;
  - reversal.
- Deposit balances are derived from active links.
- Refundability and available balance are validated.

### Vehicle finance

- Leasing-company finance agreements are separate from owner rental agreements.
- Installments contain principal, interest, fees and tax.
- Schedule totals and dates are validated.
- Installments track scheduled, due, partially paid, paid and overdue states.
- Payables use the core Invoice module.
- Settlement uses central payment allocations.

### Authorization

Backend permissions include separate operational, financial, approval and profitability access.

- `vehicle-rental.view`
- `vehicle-rental.financial.view`
- `vehicle-rental.profitability.view`
- reservation, agreement and rate management
- allocation, custody and replacement management
- usage record and approval
- expense record and approval
- calculation creation and approval
- financial document creation
- deposit management
- finance-agreement management

All read and write controllers enforce a relevant backend permission.

## Frontend implementation

The old frontend was removed and replaced with permission-protected React routes and split pages:

- dashboard;
- reservation list/create/detail;
- agreement list/create/detail;
- vehicle allocations and allocation detail;
- availability;
- custody events;
- atomic replacement;
- Running Chart;
- expenses and allocations;
- billing and financial document creation;
- deposits;
- vehicle finance agreements/installments;
- grouped rental reports.

Shared API, types, permission constants and reusable Vehicle Rental components are separated from page code.

## Reporting integration

The Reporting catalog now uses the rebuilt tables and central financial documents for:

- fleet availability;
- agreement register;
- active/overdue agreements;
- allocation history;
- custody history;
- replacement history;
- Running Chart;
- driver OT including triple OT;
- customer revenue calculations;
- owner cost calculations;
- customer invoices;
- owner payables;
- customer/owner outstanding;
- deposits;
- expenses and owner deductions;
- vehicle finance installments;
- profitability;
- tax traceability.

Financial statements and outstanding values remain owned by the Finance/Invoice/Payment modules.

## Cross-module updates

- Vehicle Rental service provider registered in `bootstrap/providers.php`.
- Vehicle Rental seeder registered before Super Admin permission synchronization.
- Customer and Supplier delete blockers updated for the new foreign-key tables.
- Router and navigation updated for the new frontend.
- Reporting catalog updated for the new schema.
- Migration baseline test updated from 216 to 222 module migrations.

## Tests added

- `tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
  - verifies all 24 target tables;
  - verifies legacy tables remain removed;
  - verifies core financial services remain authoritative;
  - verifies direct allocation lifecycle bypass routes are absent.
- `resources/js/modules/vehicle-rental/vehicleRentalPermissions.test.ts`
  - verifies permission uniqueness and namespace;
  - verifies operational, financial, approval and profitability separation.

## Verification completed

- PHP syntax lint: **151 files passed**.
- Module migration baseline scan: **222 migrations, 222 unique tables, 0 violations**.
- Old Vehicle Rental class/table reference scan: **no runtime references found**.
- TypeScript typecheck: **passed**.
- Vehicle Rental ESLint: **passed**.
- Targeted Vitest: **2 tests passed**.
- Vite production build: **passed**.

## Verification not executable in this sandbox

The uploaded repository did not include `vendor`, and a Composer executable was unavailable. Therefore these commands could not be executed here:

```bash
php artisan migrate:fresh --seed
php artisan test
```

The source and migration checks above passed, but Laravel runtime/database integration must still be executed in the normal project environment before merge.

Recommended final verification:

```bash
composer install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan migrate
php artisan test
npm ci
npm run typecheck
npm run lint
npm run test
npm run build
```

## Core-module boundary conclusion

Rental-specific business rules belong in Vehicle Rental:

- agreements;
- rental rates;
- allocations;
- custody;
- Running Chart;
- rental calculations;
- deposits;
- vehicle finance scheduling.

Shared rules remain in their core modules:

- customer/supplier/vehicle/employee identity;
- invoice calculation and status;
- payment creation and allocation;
- tax calculation;
- finance posting and statements;
- attachments and sequence numbering.

This avoids duplicated financial engines while keeping Vehicle Rental domain logic out of unrelated core services.
