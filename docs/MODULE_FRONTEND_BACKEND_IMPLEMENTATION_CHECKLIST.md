# Module Frontend/Backend Implementation Checklist

Generated from `app/Modules`, `ARCHITECTURE.md`, and `application_business_context_requirements.md`.

Last reviewed: 2026-05-29.

## Global Contract

- Frontend must collect input, call APIs, display backend previews/results, and allow edits only where backend validation permits.
- Backend must own tax, discounts, totals, stock movement, finance posting, payments, UOM conversion, document generation, status transitions, approvals, returns/refunds, audit/history, and tenant validation.
- Every transactional write must run under a backend transaction when it writes header plus lines, inventory plus stock levels, document plus status history, payment plus allocation, or finance posting plus ledger lines.
- Every response must expose backend-calculated values, status, tenant/organization context, audit timestamps, and related line/detail collections where the workflow needs them.
- Every module route group uses current tenant middleware except intentionally public/auth/bootstrap flows; frontend must not pass tenant IDs as a trust boundary.

## Verification Summary

- `php artisan route:list --path=api` loads successfully and reports 1,170 API routes.
- All module controllers referenced by module route files are registered. Only `AbstractUserCrudController` is intentionally unrouted.
- Route/controller container resolution for module routes succeeds. The only resolution failures are non-module Passport OAuth routes due local key/env setup.
- No missing controller classes were found for `app/Modules/*/routes/api.php`.
- Focused feature tests for Sales, Purchase, VehicleService, and VehicleRental pass after backend-calculation hardening. The full suite still has unrelated Auth/Tenant/User/Example failures and `.phpunit.result.cache` write warnings in this sandbox.

## Shared Response Structure

All resource responses should follow:

```json
{
  "data": {
    "id": 1,
    "tenant_id": 1,
    "organization_unit_id": 1,
    "status": "draft",
    "calculated_fields": {},
    "lines": [],
    "metadata": {},
    "created_at": "ISO-8601",
    "updated_at": "ISO-8601"
  }
}
```

List endpoints should return Laravel paginated resources with `data`, `links`, and `meta`. Engine/preview endpoints should return backend-calculated previews only, never require frontend-calculated totals as authoritative input.

## Authoritative Calculation Contract

Frontend payloads may include user-entered quantities, selected item/UOM/tax group/rate IDs, requested discount type/value where a module supports it, dates, parties, notes, and source references. Frontend payloads must not be trusted for `discount_amount`, `tax_amount`, `subtotal`, `line_total`, `line_total_with_tax`, `grand_total`, `balance`, inventory quantities, finance postings, allocation balances, status transitions, approval state, document numbers, or tenant ownership.

Backend services must recalculate and persist those values before returning responses. If no valid tax group or pricing/discount rule exists, the backend must return a zero or rejected calculation according to the module rule instead of accepting a frontend-calculated fallback.

## Module Checklists

### Audit

1. Frontend endpoint checklist: `GET /api/audit/audit-logs`, `GET /api/audit/audit-logs/{id}`.
2. Missing endpoint list: export/filter presets, entity timeline shortcut `GET /api/audit/entities/{type}/{id}/history`.
3. Required request payloads: list filters by tenant, actor, action, entity, date range; no frontend mutation payloads.
4. Required response structures: immutable audit log with actor, entity reference, event/action, old/new values, IP/user agent, timestamps.
5. Backend-only responsibilities: audit capture, normalization, redaction, retention.
6. Frontend-only responsibilities: search, filter, display timeline.
7. Business logic in backend: deciding what is auditable and immutable.
8. Calculations in backend: none except diff summaries/counts.
9. Validation rules: entity type/id, date ranges, tenant scope.
10. Transaction/rollback requirements: audit writes must be in the same transaction as the source business action or queued with guaranteed delivery.
11. Core dependencies: Core, Tenant, User.
12. Missing features: entity timeline shortcut, export.
13. Issues/fixes required: Passport env key failure is external; module route wiring is valid.

### Auth

1. Frontend endpoint checklist: login, register, refresh, token issue/exchange/validate, SSO callback, verification request/verify, logout, me, sessions list/revoke, client authorize, external identity link/unlink.
2. Missing endpoint list: password reset/change, MFA device management, SSO provider metadata discovery.
3. Required request payloads: credentials or provider tokens, refresh token, client credentials, verification challenge, session id.
4. Required response structures: token payload, user context, tenant memberships, roles/permissions, expiry/session metadata.
5. Backend-only responsibilities: password hashing, token signing, session revocation, external identity validation.
6. Frontend-only responsibilities: collect credentials, store tokens securely, redirect to SSO.
7. Business logic in backend: auth policy, tenant access, session validity.
8. Calculations in backend: expiries, token scopes, challenge TTL.
9. Validation rules: credential format, provider/client ids, tenant access, token integrity.
10. Transaction/rollback requirements: registration plus user/tenant records; identity link/unlink; logout/session revoke.
11. Core dependencies: Core, Tenant, User.
12. Missing features: password reset/MFA.
13. Issues/fixes required: local Passport key/env setup must be fixed before OAuth routes can resolve.

### Configuration

1. Frontend endpoint checklist: CRUD entries, resolve entry, feature enabled, clear cache, CRUD countries/currencies/languages/timezones.
2. Missing endpoint list: bulk configuration import/export, setting schema discovery.
3. Required request payloads: key, scope, value, type, module, tenant/org unit scope, active flags.
4. Required response structures: resolved value, raw value, scope precedence, timestamps.
5. Backend-only responsibilities: config resolution, cache invalidation, precedence.
6. Frontend-only responsibilities: edit setting forms and display resolved values.
7. Business logic in backend: feature-flag decisions and config inheritance.
8. Calculations in backend: effective setting resolution.
9. Validation rules: key uniqueness per scope, valid type/value.
10. Transaction/rollback requirements: setting writes plus cache clear.
11. Core dependencies: Core, Tenant, OrganizationUnit.
12. Missing features: schema discovery endpoint.
13. Issues/fixes required: no broken endpoints found.

### Customer

1. Frontend endpoint checklist: CRUD customers, contacts, addresses, vehicles; lookup; status; validate for sales/vehicle rental/vehicle service; finance defaults get/update; credit check; tax profile; user access list/create/link/deactivate/unlink.
2. Missing endpoint list: customer statement, aging summary, duplicate detection.
3. Required request payloads: master data, contacts, addresses, vehicles, status action, credit profile/default finance accounts.
4. Required response structures: customer profile with contacts/addresses/vehicles, credit/tax/finance defaults, validation result.
5. Backend-only responsibilities: credit checks, status transitions, tax/finance default validation, tenant-safe user linking.
6. Frontend-only responsibilities: collect customer info and show validation messages.
7. Business logic in backend: eligibility for sales/service/rental.
8. Calculations in backend: credit exposure, balances, aging.
9. Validation rules: unique code per tenant, valid tax/finance refs, status transition validity.
10. Transaction/rollback requirements: customer with contacts/addresses; user-access changes.
11. Core dependencies: Tenant, User, Finance, Vehicle.
12. Missing features: statements and aging.
13. Issues/fixes required: no broken endpoints found.

### Document

1. Frontend endpoint checklist: CRUD/list documents; change status; attachments; comments; activities; events; permissions; relations; document/item types; definitions.
2. Missing endpoint list: render/download document, PDF/email/send endpoints, template preview.
3. Required request payloads: document type/definition, source refs, status action, attachment/comment/activity/event payloads.
4. Required response structures: document header, status, source refs, relation graph, attachments/comments/activities/events.
5. Backend-only responsibilities: document generation, numbering, status workflow, permission enforcement.
6. Frontend-only responsibilities: upload files, show document previews returned by backend.
7. Business logic in backend: document lifecycle and template selection.
8. Calculations in backend: sequence numbers, versioning.
9. Validation rules: source ref exists in tenant, valid status transition, file constraints.
10. Transaction/rollback requirements: document plus attachments/status/history/relations.
11. Core dependencies: Core, Tenant, Sequence, Audit.
12. Missing features: render/download/template preview endpoints.
13. Issues/fixes required: no broken endpoints found.

### Extension

1. Frontend endpoint checklist: CRUD attachments, entity attributes, comments.
2. Missing endpoint list: entity-scoped shortcuts, attachment download/preview.
3. Required request payloads: entity type/id, attribute key/value, comment body, file metadata.
4. Required response structures: attachment/comment/attribute with entity refs and audit metadata.
5. Backend-only responsibilities: file storage, entity reference validation, audit.
6. Frontend-only responsibilities: collect files/comments/custom fields.
7. Business logic in backend: extension permission and target entity validation.
8. Calculations in backend: none except file hashes/sizes.
9. Validation rules: entity refs, allowed attribute schema, file constraints.
10. Transaction/rollback requirements: attachment metadata plus storage failure compensation.
11. Core dependencies: Core, Tenant, Document/Audit where used.
12. Missing features: download/preview shortcuts.
13. Issues/fixes required: no broken endpoints found.

### Finance

1. Frontend endpoint checklist: CRUD accounts, fiscal years/periods, payment terms, tax groups/rates/rules, AP/AR transactions, cost centers, journal entries/lines, budgets/lines, bank accounts/transactions/reconciliations/category rules; journal engine preview/post/reverse; tax preview calculation.
2. Missing endpoint list: trial balance, ledger report, account statement, period close/reopen, tax report.
3. Required request payloads: account/payment/tax/journal data, posting source refs, debit/credit lines, period/date/currency.
4. Required response structures: balanced journal, posting preview, tax breakdown, account/ledger summaries.
5. Backend-only responsibilities: tax calculation, postings, ledgers, period locks, exchange-rate handling.
6. Frontend-only responsibilities: collect finance setup and show previews.
7. Business logic in backend: chart mapping, posting rules, period validation.
8. Calculations in backend: tax, debit/credit totals, balances, reconciliations.
9. Validation rules: balanced journals, open period, valid accounts/currency/tax rules, tenant scope.
10. Transaction/rollback requirements: posting and reversal with all journal lines and AP/AR changes.
11. Core dependencies: Core, Tenant, Configuration, Voucher, Payment.
12. Missing features: reporting and period close endpoints.
13. Issues/fixes required: no broken endpoints found.

### HR

1. Frontend endpoint checklist: CRUD departments, designations, employment types, employees, contacts, addresses, documents, contracts, biometric devices, holidays, attendance logs/records, shifts/assignments, leave types/policies/lines/allocations/applications, salary components/structures/lines, salary assignments, payroll runs, payslips/lines, performance cycles/reviews; employee lookup/active/by department/by designation/status/employment details/user access.
2. Missing endpoint list: payroll calculation preview/finalize, leave approval workflow, attendance import, payslip post-to-finance.
3. Required request payloads: employee master, attendance/leave/payroll structures, status and user-access actions.
4. Required response structures: employee profile, payroll/payslip lines with backend-calculated amounts, leave balances.
5. Backend-only responsibilities: payroll, leave balance, attendance normalization, user access.
6. Frontend-only responsibilities: collect HR data and show calendars/payroll results.
7. Business logic in backend: employment status, leave approval, salary rules.
8. Calculations in backend: payroll, leave balances, overtime/attendance totals.
9. Validation rules: employee identity uniqueness, salary component rules, date overlaps.
10. Transaction/rollback requirements: employee aggregates; payroll run plus payslips; leave approvals.
11. Core dependencies: Tenant, OrganizationUnit, User, Finance.
12. Missing features: payroll/leave engines and finance posting endpoints.
13. Issues/fixes required: no broken endpoints found.

### Inventory

1. Frontend endpoint checklist: CRUD batches, serials, cost layers, stock levels/movements/reservations/adjustments/adjustment lines/transfers/transfer lines, receipt inspections, put-away, picking, transfer orders/lines, trace logs, valuation configs, cycle counts/lines; inventory engine allocate/valuation/dimensions where registered.
2. Missing endpoint list: stock availability preview by source document, reservation release/consume shortcuts, stock ledger report, batch/serial trace report.
3. Required request payloads: item/uom/warehouse/location/batch/serial/quantity/source refs, movement reason, valuation config.
4. Required response structures: stock movement with before/after qty, stock level, cost layer, reservation/trace refs.
5. Backend-only responsibilities: stock movements, reservations, valuation, UOM conversion, batch/serial traceability.
6. Frontend-only responsibilities: scan/select items and display availability returned by backend.
7. Business logic in backend: inventory availability, reservation/issue/receipt rules.
8. Calculations in backend: quantity conversions, stock levels, cost/valuation.
9. Validation rules: stockable item, tenant warehouse, sufficient stock, valid UOM conversion.
10. Transaction/rollback requirements: movement plus stock level/cost layer/trace updates.
11. Core dependencies: Tenant, Warehouse, Item, UOM, Audit.
12. Missing features: stock ledger/report shortcuts.
13. Issues/fixes required: no broken endpoints found.

### Item

1. Frontend endpoint checklist: CRUD item categories, brands, items, attributes/groups/values, variants/variant attributes/values, combo items, identifiers; activate/deactivate item.
2. Missing endpoint list: combo expansion preview, item availability, item price/tax defaults.
3. Required request payloads: item master, type, stockable/service flags, UOM, variants, combo component lines, identifiers.
4. Required response structures: item profile with type, UOM, stockable flags, variants, combo components.
5. Backend-only responsibilities: item type rules, combo expansion, stockability validation.
6. Frontend-only responsibilities: item setup forms and selectors.
7. Business logic in backend: whether item affects inventory, service/labor/combo semantics.
8. Calculations in backend: combo component quantities and UOM normalization.
9. Validation rules: unique SKU/code per tenant, valid UOM, valid item type.
10. Transaction/rollback requirements: item plus variants/attributes/combo lines.
11. Core dependencies: Tenant, UOM, Inventory/Pricing consumers.
12. Missing features: combo expansion preview endpoint.
13. Issues/fixes required: no broken endpoints found.

### OrganizationUnit

1. Frontend endpoint checklist: CRUD organization units, types, settings/groups, documents; assignment/context flows where registered.
2. Missing endpoint list: org tree endpoint, user assignment matrix, inherited settings preview.
3. Required request payloads: org unit hierarchy, type, status, settings, documents.
4. Required response structures: org unit with parent/path, type, settings, documents.
5. Backend-only responsibilities: hierarchy validation and context resolution.
6. Frontend-only responsibilities: tree UI and settings forms.
7. Business logic in backend: allowed hierarchy and active context.
8. Calculations in backend: path/depth/inherited settings.
9. Validation rules: no cycles, tenant scope, unique code.
10. Transaction/rollback requirements: org unit plus settings/documents.
11. Core dependencies: Core, Tenant, User.
12. Missing features: org tree/inheritance preview.
13. Issues/fixes required: no broken endpoints found.

### Payment

1. Frontend endpoint checklist: CRUD payment methods/groups, payments, allocations, advance payments/allocations, cash registers, checks, write-offs where registered; payment engine allocate/preview allocation/unallocate/status/post/reverse/refund.
2. Missing endpoint list: payment method availability preview, customer/supplier wallet statement.
3. Required request payloads: payment header, party/source refs, method, amount, currency, allocation lines, refund/write-off refs.
4. Required response structures: payment with allocations, status, posted/reversed/refunded amounts, backend allocation preview.
5. Backend-only responsibilities: payments, allocation, settlement status, posting, reversal, refund.
6. Frontend-only responsibilities: collect receipt/payment info and display backend settlement.
7. Business logic in backend: allocation eligibility and method rules.
8. Calculations in backend: allocated amount, unallocated balance, settlement state.
9. Validation rules: positive amounts, valid source refs, no over-allocation, tenant scope.
10. Transaction/rollback requirements: payment plus allocations plus finance/voucher links.
11. Core dependencies: Tenant, Finance, Voucher, Customer, Supplier.
12. Missing features: wallet/statement endpoints.
13. Issues/fixes required: no broken endpoints found.

### Pricing

1. Frontend endpoint checklist: resolve price; preview discount calculation; CRUD price lists/items, pricing rules/conditions, discounts/rules, pricing tiers, supplier price lists, customer price lists; read-only price histories.
2. Missing endpoint list: deeper automatic price-history capture for every pricing mutation.
3. Required request payloads: tenant, item, party, quantity, UOM, date, currency, source type/id, list/item/rule data; for discount preview, base amount, quantity, and discount type/value or discount list.
4. Required response structures: resolved unit price, discount breakdown, effective quantity/UOM, selected price list/rules, applied discounts, discount amount, net amount.
5. Backend-only responsibilities: pricing, discount calculation, UOM normalization.
6. Frontend-only responsibilities: request price previews and display returned breakdown.
7. Business logic in backend: price list priority, party-specific pricing, discount eligibility.
8. Calculations in backend: unit price, tiered price, discount amount, effective quantity.
9. Validation rules: active list/date/currency, item exists in tenant, valid UOM conversion.
10. Transaction/rollback requirements: price list aggregate writes and history capture.
11. Core dependencies: Tenant, Item, UOM, Customer, Supplier.
12. Missing features: richer history capture.
13. Issues/fixes required: added `POST /api/pricing/discounts/preview-calculate`; discount service now caps applied discounts at the base amount; keep frontend using `resolve-price` and discount preview for authoritative calculated pricing.

### Purchase

1. Frontend endpoint checklist: CRUD purchase orders/lines, GRN headers/lines, purchase returns/lines; invoice calculate; payment allocation preview; with-lines/sync aggregate endpoints; settings show/upsert/initialize; lookup available PO/GRN/returnable/payable lines; integration documents/payments/advances/post/reverse/refund; workflow transition/document/payment/inventory/finance/history.
2. Missing endpoint list: purchase request/RFQ, supplier invoice CRUD as first-class resource, landed cost allocation.
3. Required request payloads: supplier, document dates, item lines, quantities/UOM/prices/tax/discount inputs, workflow action, payment allocation.
4. Required response structures: document header/lines with backend subtotal/discount/tax/grand/balance, status history, integration refs.
5. Backend-only responsibilities: purchase totals, tax/discount, inventory receipt/return, AP posting, supplier payments.
6. Frontend-only responsibilities: capture PO/GRN/return/payment inputs and show backend previews.
7. Business logic in backend: optional purchase workflows, approval/status transitions.
8. Calculations in backend: line/header discounts, tax via tax groups, subtotals, grand totals, balances, received/returnable/payable quantities, AP balances.
9. Validation rules: supplier eligibility, item/UOM, non-negative qty, no over-receipt/over-return/over-payment, tenant scope.
10. Transaction/rollback requirements: header plus lines; GRN plus stock; invoice plus AP; payment plus allocation; finance post/reverse.
11. Core dependencies: Supplier, Item, UOM, Warehouse, Inventory, Finance, Payment, Document, Sequence, Audit.
12. Missing features: purchase request/RFQ and landed cost.
13. Issues/fixes required: backend hardened so preview and line sync no longer trust frontend `discount_amount`, `tax_amount`, header tax/discount amount, totals, or balance fields.

### Sales

1. Frontend endpoint checklist: CRUD sales orders/lines, GDN headers/lines, sales returns/lines; sales invoice CRUD/from SO/from GDN/from multiple GDNs/post/cancel/reverse/lines; invoice calculate; payment allocation preview; with-lines/sync aggregate endpoints; settings; lookups; integration documents/payments/advances/post/reverse/refund; workflow transition/document/payment/inventory/finance/history.
2. Missing endpoint list: quotation/proforma, credit note as first-class resource, sales price/tax preview shortcut.
3. Required request payloads: customer, dates, item/service lines, quantities/UOM/prices/tax/discount inputs, workflow action, payment allocation.
4. Required response structures: document header/lines with backend subtotal/discount/tax/grand/balance, stock/posting/payment refs.
5. Backend-only responsibilities: sales totals, tax/discount, stock issue/return, AR posting, receipts/refunds.
6. Frontend-only responsibilities: collect sale/delivery/invoice/payment inputs and display previews.
7. Business logic in backend: configurable stock deduction point, credit eligibility, status transitions.
8. Calculations in backend: line/header discounts, tax via tax groups, subtotals, grand totals, balances, deliverable/returnable/receivable quantities, AR balances.
9. Validation rules: customer eligibility, item/UOM, stock availability, no over-delivery/return/payment, tenant scope.
10. Transaction/rollback requirements: header plus lines; delivery plus stock; invoice plus AR; receipt plus allocation; finance post/reverse.
11. Core dependencies: Customer, Item, UOM, Warehouse, Inventory, Finance, Payment, Pricing, Document, Sequence, Audit.
12. Missing features: quotation/proforma/credit note resource.
13. Issues/fixes required: backend hardened so preview and line sync no longer trust frontend `discount_amount`, `tax_amount`, header tax/discount amount, totals, or balance fields.

### Sequence

1. Frontend endpoint checklist: CRUD sequences, preview number, generate number, rollback number.
2. Missing endpoint list: sequence reservation/commit workflow, sequence health/usage report.
3. Required request payloads: tenant/org unit/module/document type/date/context.
4. Required response structures: number preview/generated number, reservation/rollback metadata.
5. Backend-only responsibilities: document numbering and rollback safety.
6. Frontend-only responsibilities: show previewed numbers returned by backend.
7. Business logic in backend: sequence format, concurrency, rollback rules.
8. Calculations in backend: next number and formatted value.
9. Validation rules: sequence active, scope, date period.
10. Transaction/rollback requirements: number generation must be atomic with document creation or reserved/committed.
11. Core dependencies: Tenant, Configuration, Audit.
12. Missing features: reservation/commit endpoint.
13. Issues/fixes required: no broken endpoints found.

### Supplier

1. Frontend endpoint checklist: CRUD suppliers, contacts, addresses, vehicles, items; lookup; status; validate for purchase; finance defaults; tax profile; categories; bank accounts; user access/link/deactivate/unlink.
2. Missing endpoint list: supplier statement, aging/payables shortcut.
3. Required request payloads: supplier master, contact/address/bank/tax/finance defaults, user access.
4. Required response structures: supplier profile with finance/tax/bank/access details.
5. Backend-only responsibilities: purchase eligibility, finance/tax default validation.
6. Frontend-only responsibilities: collect supplier data and show validation.
7. Business logic in backend: supplier status and purchasing eligibility.
8. Calculations in backend: payable balance/aging.
9. Validation rules: unique code per tenant, valid bank/tax/account refs.
10. Transaction/rollback requirements: supplier aggregate and user access.
11. Core dependencies: Tenant, User, Finance.
12. Missing features: statement/aging.
13. Issues/fixes required: no broken endpoints found.

### Tenant

1. Frontend endpoint checklist: CRUD tenants, plans, domains, settings/groups, documents; activate/deactivate/suspend where registered.
2. Missing endpoint list: tenant module enablement matrix, tenant provisioning health.
3. Required request payloads: tenant master, plan/domain/settings/document data, lifecycle action.
4. Required response structures: tenant with plan, domains, settings, status, lifecycle metadata.
5. Backend-only responsibilities: tenant isolation, lifecycle, domain resolution.
6. Frontend-only responsibilities: admin forms and status display.
7. Business logic in backend: lifecycle and isolation.
8. Calculations in backend: effective plan/settings.
9. Validation rules: unique domain/code/uuid, valid status transition.
10. Transaction/rollback requirements: tenant creation plus default settings/domains.
11. Core dependencies: Core, Configuration, Audit.
12. Missing features: module enablement/provisioning health.
13. Issues/fixes required: no broken endpoints found.

### UOM

1. Frontend endpoint checklist: CRUD units of measure, UOM conversions, convert endpoint.
2. Missing endpoint list: conversion matrix and item-specific conversion preview.
3. Required request payloads: from/to UOM, item, tenant, quantity, conversion factor.
4. Required response structures: converted quantity, factor, direction, precision.
5. Backend-only responsibilities: UOM conversion and precision.
6. Frontend-only responsibilities: request conversions and show result.
7. Business logic in backend: allowed conversions and item-specific overrides.
8. Calculations in backend: converted quantity and rounding.
9. Validation rules: UOM exists, conversion exists/active, valid quantity.
10. Transaction/rollback requirements: conversion table writes.
11. Core dependencies: Tenant, Item.
12. Missing features: matrix endpoint.
13. Issues/fixes required: no broken endpoints found.

### User

1. Frontend endpoint checklist: CRUD users, roles, permissions, role permissions, user roles, user permissions, user tenants, user documents, user devices; resolve identity; activate/deactivate/suspend; organization-unit assign/remove.
2. Missing endpoint list: permission matrix, effective permissions, password/admin reset.
3. Required request payloads: user profile, identity, role/permission assignments, tenant/org unit assignments.
4. Required response structures: user with roles, permissions, tenants, org units, devices.
5. Backend-only responsibilities: identity uniqueness, effective permissions, tenant access.
6. Frontend-only responsibilities: user/admin forms and assignment UI.
7. Business logic in backend: role/permission policy and status transitions.
8. Calculations in backend: effective permission set.
9. Validation rules: unique identity/email, valid roles/tenants, status action.
10. Transaction/rollback requirements: user plus role/tenant/org assignments.
11. Core dependencies: Tenant, OrganizationUnit, Auth.
12. Missing features: effective permission matrix endpoint.
13. Issues/fixes required: no broken endpoints found.

### Vehicle

1. Frontend endpoint checklist: CRUD vehicles, vehicle documents.
2. Missing endpoint list: availability/calendar, maintenance/service/rental history shortcut.
3. Required request payloads: vehicle master, ownership/source, status, documents.
4. Required response structures: vehicle profile with documents and status.
5. Backend-only responsibilities: vehicle source/status validation.
6. Frontend-only responsibilities: vehicle forms and document uploads.
7. Business logic in backend: availability/status rules.
8. Calculations in backend: availability derived from rental/service schedules.
9. Validation rules: unique registration/VIN per tenant, valid status/source.
10. Transaction/rollback requirements: vehicle plus documents.
11. Core dependencies: Tenant, Document, Supplier/Customer where applicable.
12. Missing features: availability/history endpoint.
13. Issues/fixes required: no broken endpoints found.

### VehicleRental

1. Frontend endpoint checklist: CRUD agreements and running charts; sync agreement lines/rates/rate rules/extra charges; sync running chart lines; billing preview; replacements/breakdowns; settings; status history; vehicle availability; provider payables; workflow transition/invoice/payment/provider payable/finance post/reverse; integration invoice/payment/provider payable.
2. Missing endpoint list: rental quotation, rental calendar by vehicle/driver, damage/refund claim resource.
3. Required request payloads: agreement terms, rates/rules, running chart usage, replacement/breakdown, workflow/payment/provider payable actions.
4. Required response structures: agreement with calculated charges, running chart totals, provider payable/customer invoice previews, status history.
5. Backend-only responsibilities: rental billing, overtime/night/weekend/double-rate, provider payable, vehicle availability.
6. Frontend-only responsibilities: collect agreement/usage and show backend billing preview.
7. Business logic in backend: agreement lifecycle, with/without driver rules, replacement impact.
8. Calculations in backend: km/hour/day/month charges, extra charges, taxes via tax groups, authoritative line totals, provider margins.
9. Validation rules: vehicle availability, valid rates/rules, no overlapping agreements, tenant/provider refs.
10. Transaction/rollback requirements: agreement aggregate; running chart plus totals; invoice/payment/provider payable/finance posting.
11. Core dependencies: Vehicle, Customer, Supplier, HR/User driver refs, Pricing, Finance, Payment, Document, Sequence, Audit.
12. Missing features: quotation/calendar/damage-refund resources.
13. Issues/fixes required: agreement line and extra-charge sync now overwrite frontend `discount_amount`, `tax_amount`, and totals; request tenant checks now validate ID shape while tenant middleware/service owns tenant authorization.

### VehicleService

1. Frontend endpoint checklist: CRUD service types, job cards, job card lines, labor items/assignments, non-inventory items, inspections/lines, diagnostics/lines; aggregate job card create/update; sync lines/labor/non-inventory/customer-supplied/external services; invoice preview; settings; status history; stock availability; invoiceable/receivable job cards; workflow transition/invoice/payment/inventory/finance post/reverse; integration invoice/payment/inventory.
2. Missing endpoint list: service appointment/scheduler, combo expansion preview, technician incentive payroll export.
3. Required request payloads: job card header, vehicle/customer/supervisor, service/labor/spare/non-inventory/customer-supplied/external lines, assignments, workflow/payment actions.
4. Required response structures: job card with all calculated line totals, labor incentives, stock/invoice/payment/posting refs, status history.
5. Backend-only responsibilities: service totals, labor incentives, stock consumption, invoice/payment/finance, combo expansion.
6. Frontend-only responsibilities: collect job details and show backend-calculated previews.
7. Business logic in backend: service lifecycle, customer-supplied/non-inventory/external-service behavior.
8. Calculations in backend: line/header discounts, tax via tax groups, service invoice preview, labor incentives, stock consumption, balances.
9. Validation rules: vehicle/customer eligibility, item stockability, labor assignment shares, no over-consumption, tenant scope.
10. Transaction/rollback requirements: job aggregate; inventory post; invoice/payment; finance post/reverse.
11. Core dependencies: Customer, Vehicle, HR, Item, UOM, Inventory, Finance, Payment, Document, Sequence, Audit.
12. Missing features: scheduler/combo preview/incentive export.
13. Issues/fixes required: added `POST /api/vehicle-service/job-cards/{jobCardId}/invoice-preview` and `POST /api/vehicle-service/job-cards/{jobCardId}/non-inventory-items/sync`; job-card aggregates now recalculate line totals, taxes, discounts, incentives, header adjustments, grand total, and balance in backend.

### Voucher

1. Frontend endpoint checklist: CRUD voucher types, activate/deactivate type, CRUD vouchers, upsert lines, allocations list/add/update, submit/approve/reject/post/cancel/reverse/history, utility preview number/validate balance/validate payment method/preview posting.
2. Missing endpoint list: voucher templates, recurring vouchers, attachment shortcut.
3. Required request payloads: voucher type, header, balanced lines, allocations, workflow action, payment method.
4. Required response structures: voucher with lines/allocations/status/history/posting preview.
5. Backend-only responsibilities: balance validation, workflow, posting, reversal, allocation.
6. Frontend-only responsibilities: voucher entry UI and preview display.
7. Business logic in backend: approval rules and posting eligibility.
8. Calculations in backend: debit/credit/total amounts and balances.
9. Validation rules: balanced voucher, active type, allowed method, valid status transition.
10. Transaction/rollback requirements: voucher plus lines/allocations/approvals/postings.
11. Core dependencies: Finance, Payment, Sequence, Document, Audit.
12. Missing features: templates/recurring.
13. Issues/fixes required: no broken endpoints found.

### Warehouse

1. Frontend endpoint checklist: CRUD warehouses, warehouse locations.
2. Missing endpoint list: bin/location availability, warehouse stock summary.
3. Required request payloads: warehouse/location code, name, address, status, hierarchy/location properties.
4. Required response structures: warehouse/location with tenant/org, active flags, timestamps.
5. Backend-only responsibilities: warehouse/location uniqueness and stock safety checks.
6. Frontend-only responsibilities: warehouse setup forms.
7. Business logic in backend: location eligibility for stock operations.
8. Calculations in backend: availability/stock summaries.
9. Validation rules: unique code per tenant, valid parent/warehouse refs.
10. Transaction/rollback requirements: warehouse plus locations when bulk-created.
11. Core dependencies: Tenant, OrganizationUnit, Inventory.
12. Missing features: availability/stock summary endpoint.
13. Issues/fixes required: no broken endpoints found.

## Priority Fix Plan

1. Implemented backend hardening for Sales, Purchase, VehicleService, and VehicleRental so frontend-calculated taxes, discounts, totals, balances, and selected rental/service totals are not authoritative.
2. Added VehicleService invoice preview and non-inventory aggregate sync endpoints.
3. Fixed VehicleRental request-layer tenant validation so mocked/controller tests and tenant middleware/service responsibilities are not bypassed by direct request `exists` queries.
4. Added Pricing discount preview endpoint and capped backend discount calculation at the base amount.
5. Add report/read-model endpoints: finance ledgers/trial balance, stock ledger/trace, customer/supplier statements, HR payroll/leave previews.
6. Add document render/download/template-preview endpoints so frontend never generates official documents.
7. Add first-class workflow engines still listed as feature gaps: HR payroll/leave approval, Sales quotation/credit note, Purchase request/RFQ/landed cost, VehicleService scheduler/combo preview, VehicleRental quotation/calendar/damage/refund.
8. Fix local Passport/OAuth key configuration and existing Auth/Tenant/User test failures before relying on a full-suite green status.
