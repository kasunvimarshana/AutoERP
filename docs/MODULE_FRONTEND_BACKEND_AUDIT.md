# Module Frontend/Backend Audit

Scope: `app/Modules` migrations, routes, controllers, services/actions, requests, resources, models, tenant boundaries, backend-only calculations, preview endpoints, and frontend responsibilities.

## Global Findings

- Modules scanned: 27.
- PHP files scanned: 3,709.
- Module API routes load successfully. Inventory now has an explicit stock availability preview route.
- Every protected module route group uses current user, tenant, and organization-unit middleware unless the module is an auth/bootstrap surface.
- Most modules use migration-backed models/repositories. Document is the main exception: it uses direct `DB::table()` access for many document sub-tables inside its repository because the Document module owns those tables. Cross-module direct table access also appears in seeders for document/sequence bootstrap data.
- Generic resource classes commonly return raw `DataRecord` arrays. This is acceptable for current repository DTOs, but not ideal as an enterprise response contract because response shape is not curated per endpoint.
- VehicleRental previously had no request/resource classes. This pass added presentation validation and a response resource wrapper.

## Required Preview Endpoint Coverage

| Preview Need | Current Backend Endpoint |
| --- | --- |
| Invoice calculation | `POST /api/sales/calculate-invoice`, `POST /api/purchase/calculate-invoice`, VehicleService/Rental invoice workflow previews through management/workflow services |
| Payment allocation | `POST /api/payment/payments/{payment}/engines/preview-allocation`, Sales/Purchase/Vehicle workflows |
| Stock availability | `POST /api/inventory/engines/stock-availability/preview`, `GET /api/vehicle-service/stock-availability`, `GET /api/vehicle-rental/vehicle-availability` |
| UOM conversion | `POST /api/uom/convert` |
| Price resolving | `POST /api/pricing/resolve-price` |
| Tax calculation | `POST /api/finance/tax/preview-calculate` |
| Discount calculation | `POST /api/pricing/discounts/preview-calculate`; also included in Pricing resolver |
| Finance posting preview | `POST /api/finance/journal-entries/{journalEntry}/engines/preview-posting`, Voucher preview posting |

## Module Endpoint And Responsibility Matrix

| Module | Endpoint Surface | Missing Frontend Endpoints | Backend-Only Logic | Frontend Responsibility |
| --- | --- | --- | --- | --- |
| Audit | audit log list/show | entity timeline/export | immutable audit capture, redaction, retention | filter/display history |
| Auth | login/register/token/session/SSO/identity | password reset, MFA management | credentials, tokens, sessions, identity links | collect credentials, redirect, token handling |
| Configuration | entries, resolve, features, countries/currencies/languages/timezones | schema discovery, bulk import/export | config precedence/cache/feature decisions | settings UI |
| Core | middleware/contracts only | none | context, transactions, storage/hash utilities | no direct UI |
| Customer | customers, contacts, addresses, vehicles, lookup, status, validation, finance/tax/user access | statements, aging, duplicate detection | credit/tax/finance eligibility | collect/display customer data |
| Document | documents, status, attachments/comments/events/permissions/relations/types/definitions | render/download/PDF/email/template preview | document generation, workflow, permissions | upload/show backend-rendered docs |
| Extension | attachments, comments, entity attributes | entity-scoped shortcuts, attachment preview/download | file/entity validation | collect files/comments/custom fields |
| Finance | accounts, periods, tax, AP/AR, journals, budgets, banks, posting/tax previews | ledgers, trial balance, period close/reopen, tax reports | tax, postings, balances, period locks | display setup and previews |
| HR | employees, org HR masters, attendance, leave, salary, payroll, performance | payroll preview/finalize, leave approval, finance posting | payroll/leave/attendance calculations | collect HR inputs |
| Inventory | stock/cost/reservation/movement/adjustment/transfer/cycle/engine endpoints | stock ledger report, reservation consume/release shortcuts | stock effects, valuation, availability | scan/select/display stock |
| Item | items, variants, attributes, combos, identifiers | combo expansion preview, item availability | item type/stockability/combo expansion | item setup UI |
| OrganizationUnit | org units/types/settings/docs | org tree, inheritance preview, assignment matrix | hierarchy/context validation | tree/settings UI |
| Payment | payment masters/payments/allocations/advances/checks/write-offs/engines | wallet/statement endpoints | payment balances, allocation, settlement, posting/refund | collect payment input |
| Pricing | price lists/items, rules/conditions, discounts/rules, tiers, histories, resolve-price, discount preview | deeper history capture | price, discount, tier, UOM normalization | request/display pricing result |
| Purchase | PO/GRN/returns/invoice preview/payment preview/settings/lookups/integration/workflows | purchase request/RFQ, first-class supplier invoice, landed cost | totals, tax, discount, GRN stock, AP/posting/payment | collect purchasing inputs |
| Sales | SO/GDN/returns/invoices/invoice preview/payment preview/settings/lookups/integration/workflows | quotations/proforma, credit note resource | totals, tax, discount, stock issue, AR/posting/payment | collect sales inputs |
| Sequence | sequence CRUD/preview/generate/rollback | reservation/commit workflow | document numbering/concurrency | show backend numbers |
| Supplier | suppliers, contacts, addresses, vehicles/items, status, validation, tax/bank/finance/user access | statement/aging | supplier eligibility, tax/finance defaults | collect supplier data |
| Tenant | tenants/plans/domains/settings/docs/lifecycle | module enablement, provisioning health | tenant lifecycle/isolation | tenant admin UI |
| UOM | UOM CRUD, conversions, convert | conversion matrix | conversion and rounding | request/display conversion |
| User | users/roles/permissions/tenants/devices/org units/status | effective permission matrix, password reset | effective permissions, tenant access | user admin UI |
| Vehicle | vehicles/documents | availability/history | vehicle status/source rules | vehicle forms |
| VehicleRental | agreements, running charts, sync lines/rates/rules/charges, availability, billing preview, provider payables, workflow/integration | quotation/calendar/damage/refund resources | rental billing, availability, provider payable, workflow/posting | collect agreement/usage |
| VehicleService | service masters/job cards/lines/labor/non-inventory/diagnostics/inspections/aggregate/sync/settings/status/stock/invoice/payment/workflow | scheduler, combo preview, incentive payroll export | service totals, labor incentives, stock usage, invoice/payment/posting | collect service job inputs |
| Voucher | voucher types/vouchers/lines/allocations/workflow/utilities | templates/recurring vouchers | balance validation, approval/post/reverse | voucher entry UI |
| Warehouse | warehouses/locations | stock summary/location availability | location eligibility | setup UI |

## Fixes Implemented In This Pass

- Added `POST /api/inventory/engines/stock-availability/preview` as a non-mutating stock availability preview alias backed by the existing allocation engine.
- Added VehicleRental FormRequests for agreement lists, running chart lists, aggregate upserts, sync payloads, settings, availability, billing preview, records, and status history.
- Added `VehicleRentalRecordResource` and wrapped VehicleRental management/agreement/running-chart responses through it.
- Preserved backend ownership of rental calculations, stock availability, UOM conversion, price/tax/payment/finance preview behavior.

## Existing Fix From Previous Pass

- Added Pricing API surface for pricing rules, pricing rule conditions, discounts, discount rules, pricing tiers, and read-only price histories.
- Added Pricing discount preview endpoint and capped backend-applied discounts at the base amount.

## Backend-Only Logic Rules To Enforce

- Tax, discount, invoice totals, payment balances, finance postings, stock effects, UOM conversions, workflow statuses, returns, refunds, and reversals must remain in application/domain services.
- Frontend payloads may include user-entered quantities, selected rates, dates, party IDs, item IDs, requested discounts, tax group IDs, and notes, but backend must return authoritative calculated values.
- Official documents must be generated/rendered by backend Document services.

## Transaction/Rollback Requirements

- Already present in key aggregate services: Sales/Purchase aggregate writes, VehicleRental agreement/running-chart aggregates, Inventory stock movements, Payment engines, Voucher workflows.
- Must remain mandatory for any flow writing header + lines, document + history, payment + allocation, stock movement + stock level/cost layer, or finance posting + journal lines.

## Remaining Risks

- Document module contains large direct `DB::table()` repository methods. This is intra-module table ownership, but it is less clean than repository-per-aggregate models and should be refactored gradually.
- Seeders in Sales/Purchase/Voucher/VehicleRental/VehicleService directly access `tenants`, `document_*`, and `sequences`. This is bootstrap-time coupling, not runtime workflow coupling, but still deserves catalog/helper services.
- Many resource classes expose raw `DataRecord` arrays. Enterprise APIs should eventually move to explicit response fields for each public endpoint.
- Several modules have CRUD for tables but no richer workflow endpoints: HR payroll/leave, Finance reports/period close, Sales quotations/credit notes, Purchase RFQ/landed cost, VehicleService scheduler/combo preview, VehicleRental quotation/calendar/damage/refund.
- “Every field has purpose” is only partially satisfied from code structure. A field-level semantic audit requires validating each migration column against business workflows and UI screens.
