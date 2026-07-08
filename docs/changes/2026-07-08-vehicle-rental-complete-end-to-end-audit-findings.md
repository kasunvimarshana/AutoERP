# Vehicle Rental Complete End-to-End Audit Findings

## Scope

Audited the Vehicle Rental flows end to end for:

- Lessor agreements, vehicle allocations, running-chart cost contexts, owner payables, and supplier payment handoff.
- Lessee agreements, vehicle allocations, running-chart revenue contexts, customer invoices, deposits, and customer payment handoff.
- Vehicle finance agreements and installment payable generation.
- Backend services/controllers/resources/requests, frontend rental pages/lookups/API contracts, migrations, local Laravel log, route registration, and focused tests.

## Findings

### 1. MySQL Vehicle Rental migration chain is blocked

`storage/logs/laravel.log` shows MySQL failing on `2026_06_12_200022_create_rental_deposit_requirements_table.php` while adding `rental_deposit_req_agreement_kind_customer_fk`.

Root cause: `rental_agreements.agreement_kind` is `string(30)`, but `rental_deposit_requirements.agreement_kind` is a MySQL `enum`. The composite foreign key columns are not definition-compatible.

Impact: local MySQL stops at `2026_06_12_200022_create_rental_deposit_requirements_table`; `rental_deposit_links`, `rental_status_histories`, `rental_calculation_sources`, `rental_usage_facts`, and `audit_logs` remain pending. That blocks real end-to-end rental deposits, calculation source tracking, usage facts, and audit logging even though the SQLite test suite passes.

Recommended fix: keep the child and parent agreement-kind columns definition-compatible. The cleanest immediate schema fix is to make `rental_deposit_requirements.agreement_kind` match the parent `string(30)` column and keep the customer-rental invariant through the existing service/request/database relationship checks.

### 2. Test database coverage misses this production-class migration failure

`phpunit.xml` runs tests with SQLite in memory, so the Vehicle Rental PHPUnit suite passes while local MySQL migration status is still broken.

Impact: MySQL-specific foreign-key compatibility regressions can ship unnoticed. This already happened with the deposit requirement composite FK.

Recommended fix: add a MySQL migration smoke check to CI/local verification for module migrations, at minimum `php artisan migrate:fresh` against the supported MySQL/MariaDB version.

### 3. Rental invoice/payable due dates ignore rental agreement payment terms

Rental agreements store and expose `payment_term_days`, but `RentalBillingPage.tsx` creates both lessee invoices and lessor owner payables with `invoice_date` and `due_date` set to the same current business date. `RentalInvoiceIntegrationService` then persists the passed due date directly.

Impact: customer invoices and owner payables can become immediately due even when the agreement defines 30-day or other payment terms, which will distort receivables/payables aging and collection/payment workflows.

Recommended fix: make the backend invoice integration the source of truth for default due dates. If the request omits `due_date`, derive it from the rental agreement payment terms; the frontend should either omit the due date or display the backend-derived default before submit.

### 4. Cost-side owner-payable path lacks real feature-level coverage

The main `RentalCalculationEndToEndFixTest` creates only revenue-side usage contexts and facts. Cost-side behavior is currently covered mostly by static contract assertions and small frontend tests.

Impact: lessor-specific regressions in owner supply allocation linkage, owner cost usage contexts, negative owner deductions, withholding/debit-note adjustment mapping, and inbound rental payable generation can pass the current focused suite.

Recommended fix: add a true owner-supply feature test: owner agreement + owner allocation + customer agreement + linked customer allocation + running chart with owner-applicable events + approved cost facts + cost calculation + payable generation. Include at least one owner deduction and withholding case.

### 5. Deposit invoice selection is backend-safe but UI is too broad

The deposit service correctly validates invoice tenant, organization unit, customer, currency, and payable state before applying or forfeiting a deposit. The frontend lookup, however, uses the generic invoice search without scoping it to the selected rental customer, rental invoice direction, balance status, or currency.

Impact: users can select an unrelated invoice and only learn through a backend error. This violates the guided relationship-entry goal for rental workflows.

Recommended fix: extend `RentalInvoiceLookupSelect` to accept invoice filters and pass the selected deposit agreement's customer, rental outbound direction, relevant status/balance filters, and currency where the invoice API supports them. Keep the backend validation as the authoritative guard.

## Verified Guardrails

- Lessor agreements are modeled as `owner_supply`; lessee agreements are modeled as `customer_rental`.
- Security deposits are restricted to customer-rental agreements in service logic and UI routing.
- Owner-supplied customer allocations require a valid active owner source allocation and expected source row version.
- Running charts record one physical usage log and create revenue/cost commercial contexts where applicable.
- Calculation transitions use expected versions through `RentalCalculationTransitionService`.
- Rental invoice integration maps customer rentals to outbound customer invoices and owner-supply costs to inbound supplier payables.
- Core invoice status updates auto-bump invoice `row_version`; payment creation/allocation/posting are owned by the Payment module.

## Verification

Ran:

- `php artisan migrate:status --no-interaction` - confirmed Vehicle Rental migrations are pending from `2026_06_12_200022_create_rental_deposit_requirements_table`.
- `php artisan test tests/Feature/VehicleRental tests/Unit/VehicleRental` - 29 passed.
- `npm run typecheck -- --pretty false` - passed.
- `npx vitest run resources/js/modules/vehicle-rental/vehicleRentalPermissions.test.ts resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalExpensePage.test.tsx --reporter=dot` - 5 files, 15 tests passed.
- `php artisan route:list --path=vehicle-rental` - 56 Vehicle Rental routes registered.
- `git diff --check` - passed before this audit record was added.
