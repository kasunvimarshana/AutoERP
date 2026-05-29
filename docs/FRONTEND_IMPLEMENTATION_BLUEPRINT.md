# Frontend Implementation Blueprint

Generated for the modular ERP/business management application from the backend module architecture in `app/Modules`.

Last updated: 2026-05-29.

## 1. Architecture Contract

The frontend is an input, workflow, preview, confirmation, and display application. It must not become a shadow ERP engine.

Frontend must not calculate:

- Invoice totals, taxes, discounts, payment balances, finance postings, stock effects, UOM conversions, pricing rules, workflow statuses, returns, refunds, reversals, rental final billing, or vehicle service job invoice totals.
- Any value persisted as `subtotal`, `discount_amount`, `tax_amount`, `line_total`, `line_total_with_tax`, `grand_total`, `balance`, stock quantity effect, journal amount, allocation balance, document number, or workflow state.

Frontend must:

- Collect user input.
- Call backend list, CRUD, preview, workflow, and posting APIs.
- Display backend preview/calculation results.
- Allow edits only to backend-permitted input fields.
- Submit confirmed records.
- Render backend status, history, audit, documents, attachments, comments, and validation errors.

Backend must remain authoritative for:

- Tenant and organization-unit isolation.
- Tax, discount, pricing, UOM, stock, payment, finance, document, workflow, approval, return/refund/reversal, audit, and history logic.
- Transactions and rollbacks for header plus lines, posting, allocation, inventory, and workflow operations.

## 2. Recommended App Folder Structure

```text
src/
  app/
    providers/
    router/
    store/
    query-client/
  layouts/
    AuthLayout/
    AppLayout/
    ModuleLayout/
  modules/
    auth/
    dashboard/
    document/
    finance/
    inventory/
    payment/
    item/
    uom/
    pricing/
    supplier/
    customer/
    hr/
    tenant/
    configuration/
    purchase/
    sales/
    vehicle-service/
    vehicle-rental/
    voucher/
  shared/
    api/
    components/
    forms/
    hooks/
    permissions/
    schemas/
    types/
    utils/
  assets/
  styles/
```

Module folder pattern:

```text
modules/{module}/
  api.ts
  routes.tsx
  permissions.ts
  types.ts
  pages/
    {Entity}ListPage.tsx
    {Entity}FormPage.tsx
    {Entity}DetailPage.tsx
    {Module}DashboardPage.tsx
    {Module}SettingsPage.tsx
  components/
  forms/
  tables/
```

## 3. State Management Approach

Use server state first:

- TanStack Query or equivalent for API data, caching, pagination, invalidation, optimistic-free updates, and background refetch.
- A small client store for auth session, active tenant, active organization unit, sidebar state, UI preferences, and transient draft wizard state.
- Form state kept local with React Hook Form or equivalent.
- Preview state is server state keyed by current form inputs, never hand-calculated in global state.

Mutation rules:

- Use explicit mutations for create, update, delete, workflow action, preview, post, reverse, allocate, refund.
- Invalidate list/detail queries after successful mutation.
- Never mutate cached calculated values manually except by replacing them with backend responses.

## 4. API Client Rules

Every request must include auth and context headers/tokens expected by backend middleware.

Standard client responsibilities:

- Attach bearer token/session credentials.
- Attach active tenant and organization-unit context if the backend expects context headers.
- Normalize Laravel validation errors into field errors.
- Normalize list responses as `data`, `links`, `meta`.
- Normalize resource responses as `data`.
- Keep preview responses as returned by backend.

Common response shapes:

```json
{
  "data": {},
  "links": {},
  "meta": {}
}
```

```json
{
  "id": 1,
  "tenant_id": 1,
  "organization_unit_id": 1,
  "status": "draft",
  "lines": [],
  "metadata": {},
  "created_at": "ISO-8601",
  "updated_at": "ISO-8601"
}
```

Preview response shape:

```json
{
  "input": {},
  "calculated": {},
  "breakdown": [],
  "warnings": [],
  "errors": []
}
```

If an existing backend endpoint returns a flatter preview shape, the module API adapter should preserve the raw data and optionally expose display helpers without changing business values.

## 5. Layouts

Auth layout:

- Login, register, verification, SSO callback, token validation, session expiry.
- No sidebar.

Main app layout:

- Sidebar navigation.
- Top navigation with tenant switcher, organization-unit switcher, global search, notifications, user menu.
- Permission-aware route outlet.
- Breadcrumbs and command bar.

Module layout:

- Module dashboard.
- Secondary module tabs where needed.
- List, form, detail, settings, history, and workflow panels.

Page composition:

- List pages: title, SearchFilterBar, DataTable, ActionDropdown, bulk actions where safe.
- Form pages: FormSection groups, sticky submit/preview bar, backend validation summary.
- Detail pages: header summary, StatusBadge, tabs, WorkflowActionPanel, AuditTimeline, AttachmentManager, CommentPanel.

## 6. Navigation Structure

Primary navigation:

- Dashboard
- Master Data
  - Customers
  - Suppliers
  - Employees
  - Items
  - UOM
  - Pricing
- Operations
  - Sales
  - Purchase
  - Vehicle Service
  - Vehicle Rental
  - Inventory
  - Payments
  - Vouchers
- Finance
  - Chart of Accounts
  - Journal Entries
  - AP/AR
  - Tax
  - Banks
  - Budgets
- Documents
  - Document Records
  - Definitions
  - Types
  - Workflows
  - Sequences
- Administration
  - Tenant
  - Users and Permissions
  - Organization Units
  - Configuration
  - Audit Logs

Menu items must be hidden or disabled by permission. Disabled items should explain missing permission only where appropriate.

## 7. Global Route Map

Authentication:

- `/login`
- `/register`
- `/verify`
- `/sso/callback`
- `/sessions`

Core app:

- `/`
- `/dashboard`
- `/settings/profile`
- `/settings/sessions`

Module route pattern:

- `/{module}`
- `/{module}/{entity}`
- `/{module}/{entity}/new`
- `/{module}/{entity}/:id`
- `/{module}/{entity}/:id/edit`
- `/{module}/settings`

Business workflow route pattern:

- `/{module}/{document}/:id/workflow`
- `/{module}/{document}/:id/payments`
- `/{module}/{document}/:id/documents`
- `/{module}/{document}/:id/history`

## 8. Reusable Components

Data and page components:

- `DataTable`, `SearchFilterBar`, `StatusBadge`, `ActionDropdown`, `ConfirmDialog`, `PageHeader`, `SummaryStrip`, `EmptyState`, `ErrorState`, `PaginationControls`.

Form components:

- `FormSection`, `DynamicFormRenderer`, `FieldError`, `MoneyInput`, `QuantityInput`, `DateRangeInput`, `MetadataEditor`.

Selector components:

- `UomSelector`, `ItemSelector`, `CustomerSelector`, `SupplierSelector`, `EmployeeSelector`, `VehicleSelector`, `WarehouseSelector`, `AccountSelector`, `TaxGroupSelector`, `PaymentMethodSelector`, `DocumentTypeSelector`.

Business preview components:

- `DocumentPreview`, `WorkflowTimeline`, `AuditTimeline`, `AttachmentManager`, `CommentPanel`, `PaymentAllocationTable`, `TaxBreakdownPanel`, `DiscountPanel`, `StockAvailabilityIndicator`, `PricePreviewPanel`, `FinancePostingPreview`, `UomConversionPreview`, `RentalBillingPreview`, `ServiceInvoicePreview`.

Reusable forms:

- `AddressForm`, `ContactForm`, `BankAccountForm`, `UserAccessPanel`, `FinanceDefaultsForm`, `TaxProfileForm`, `LineItemsEditor`, `WorkflowActionPanel`, `DocumentActionPanel`.

## 9. Validation UX

- Client validation should only check required fields, formats, simple ranges, and local UI completeness.
- Backend validation remains authoritative.
- Show field errors from Laravel validation under each field.
- Show global errors in a dismissible alert.
- For critical flows, use Preview, Confirm, Submit.
- Disable submit while preview is stale when the backend requires a preview.
- On backend warnings, allow continue only when the response marks warnings as non-blocking.

## 10. Permission Model

Permission naming pattern:

- `{module}.{entity}.view`
- `{module}.{entity}.create`
- `{module}.{entity}.update`
- `{module}.{entity}.delete`
- `{module}.{entity}.export`
- `{module}.{entity}.approve`
- `{module}.{entity}.post`
- `{module}.{entity}.reverse`
- `{module}.{entity}.pay`
- `{module}.{entity}.settings`

UI enforcement:

- Route guard blocks unauthorized pages.
- Navigation hides unauthorized modules.
- Buttons/actions are permission-gated.
- Backend still enforces every permission. Frontend permissions are UX only.

## 11. Backend Preview Endpoint List

Use these before posting or confirming critical records:

| Preview | Endpoint |
| --- | --- |
| Sales invoice calculation | `POST /api/sales/calculate-invoice` |
| Purchase invoice calculation | `POST /api/purchase/calculate-invoice` |
| Vehicle service invoice | `POST /api/vehicle-service/job-cards/{jobCardId}/invoice-preview` |
| Vehicle rental billing | `POST /api/vehicle-rental/agreements/{agreementId}/billing-preview` |
| Sales payment allocation | `POST /api/sales/preview-payment-allocation` |
| Purchase payment allocation | `POST /api/purchase/preview-payment-allocation` |
| Payment engine allocation | `POST /api/payment/payments/{payment}/engines/preview-allocation` |
| Stock availability | `POST /api/inventory/engines/stock-availability/preview` |
| Vehicle service stock availability | `GET /api/vehicle-service/stock-availability` |
| Vehicle rental availability | `GET /api/vehicle-rental/vehicle-availability` |
| UOM conversion | `POST /api/uom/convert` |
| Price resolving | `POST /api/pricing/resolve-price` |
| Discount calculation | `POST /api/pricing/discounts/preview-calculate` |
| Tax calculation | `POST /api/finance/tax/preview-calculate` |
| Finance journal posting | `POST /api/finance/journal-entries/{journalEntry}/engines/preview-posting` |
| Voucher posting | `GET /api/voucher/utilities/{voucher}/preview-posting` |
| Voucher number preview | `POST /api/voucher/utilities/preview-number` |
| Sequence number preview | `POST /api/sequence/sequences/preview-number` |

Missing or future preview endpoints:

- Document render/template preview.
- HR payroll preview.
- HR leave approval impact preview.
- Item combo expansion preview.
- Purchase landed cost allocation preview.
- Sales quotation/proforma preview if added.
- Rental running-chart standalone calculation preview if separated from agreement billing preview.

## 11.1 Module Responsibility And Data Contract Matrix

| Module | Main request payloads | Main response structures | Backend-only logic | Frontend-only responsibilities |
| --- | --- | --- | --- | --- |
| Document | Type/definition, source refs, metadata, status action, attachment/comment payloads | Document header, status, version, relations, attachments, comments, activities | Numbering, rendering, versioning, permissions, workflow | Upload files, edit metadata, request previews, show history |
| Finance | Accounts, periods, journal lines, tax setup, bank/budget fields | Accounts, balanced journals, tax breakdowns, posting previews, AP/AR rows | Double-entry, period locks, tax, AP/AR, posting/reversal | Enter finance setup and journals, display previews |
| Inventory | Item, UOM, warehouse/location, batch/serial, quantity, source refs | Stock levels, movements, reservations, cost layers, availability previews | Stock effects, reservations, valuation, UOM conversion, traceability | Select/scan stock inputs and show backend availability |
| Payment | Party/source refs, method, amount, currency, allocation targets | Payment, allocations, unallocated amount, status, refund/reversal data | Allocation, settlement, balances, posting, refunds, write-offs | Capture payment info and display settlement results |
| Item | Item master, units, attributes, variants, combo lines, metadata | Item profile, variants, combo lines, identifiers, active state | Item type rules, combo validation, UOM validation | Maintain item setup and selectors |
| UOM | Unit data, conversion factors, from/to UOM, quantity | Conversion result, factor, precision, direction | Compatibility, conversion, rounding | Request conversion and show result |
| Pricing | Price list/rule/discount/tier data, item/party/date/UOM preview context | Resolved price, applied tiers/discounts, net amount, history rows | Price priority, tiers, discounts, UOM/date/currency logic | Request price/discount previews and display breakdown |
| Supplier | Supplier master, contacts, addresses, bank/tax/defaults, optional user access | Supplier profile, contacts, defaults, user access, validation result | Supplier eligibility, finance/tax validation, optional user linking | Maintain supplier record and access UI |
| Customer | Customer master, contacts, addresses, vehicles, tax/credit/defaults, optional user access | Customer profile, credit/tax/defaults, user access, validation result | Credit/outstanding checks, eligibility, optional user linking | Maintain customer record and display checks |
| HR | Employee master, contacts, addresses, employment details, payroll/leave inputs, optional user access | Employee profile, status history, payroll/leave rows when available | Employee validation, payroll, leave, attendance, user linking | Maintain employee records and HR input screens |
| Tenant | Tenant, plans, domains, settings, lifecycle action | Tenant profile, settings, domains, lifecycle state | Tenant isolation, lifecycle, provisioning, effective settings | Tenant admin forms and status display |
| Configuration | Setting keys/values/scopes, feature keys, locale/currency data | Raw/resolved settings, feature state, reference records | Config precedence, cache, feature decisions | Settings UI and reference-data maintenance |
| Purchase | Supplier, dates, source refs, item lines, UOM, price, tax group, discount inputs, workflow/payment action | PO/GRN/invoice/return/payment with calculated totals, status, refs | Tax, discounts, totals, stock receipt, AP, payments, rollback | Enter purchase documents, call previews, confirm workflow |
| Sales | Customer, dates, source refs, item lines, UOM, pricing/tax/discount inputs, workflow/payment action | SO/GDN/invoice/return/payment with calculated totals, stock/payment refs | Pricing, tax, discounts, stock issue, AR, COGS, payments, returns | Enter sales documents, call previews, confirm workflow |
| VehicleService | Job header, customer/vehicle, labor/spares/non-inventory/external/customer-supplied lines, assignments | Job card, invoice preview, stock/payment/posting refs, status history | Invoice totals, labor incentives, stock consumption, finance/payment | Maintain job card input and show service previews |
| VehicleRental | Agreement terms, vehicle/driver/provider, rates/rules, running chart usage, replacement/breakdown inputs | Agreement, billing preview, running chart totals, provider payable, status | Availability, rental billing, running chart charges, provider payable, finance/payment | Capture agreement/usage and show billing previews |
| Voucher | Voucher type/header, debit/credit lines, allocations, workflow action | Voucher, lines, allocations, balance validation, posting preview, history | Balance validation, approval, posting, payment integration, reversal | Enter voucher lines and confirm backend workflow |

## 12. Module Blueprints

### Document

Frontend routes:

- `/documents`
- `/documents/records`
- `/documents/records/:id`
- `/documents/types`
- `/documents/definitions`
- `/documents/templates`
- `/documents/workflows`
- `/documents/sequences`
- `/documents/history/:entityType/:entityId`

Screens:

- Document Records, Document Preview, Document History, Attachments/Comments, Document Types, Definitions, Templates, Sequences, Workflows, Steps, Transitions.

APIs:

- `GET/POST /api/document/documents`
- `GET /api/document/documents/{document}`
- `PATCH /api/document/documents/{document}/status`
- `GET/POST /api/document/documents/{document}/attachments`
- `GET/POST /api/document/documents/{document}/comments`
- `GET/POST /api/document/documents/{document}/activities`
- `GET/POST /api/document/documents/{document}/events`
- `GET/POST /api/document/documents/{document}/permissions`
- `GET/POST /api/document/documents/{document}/relations`
- `GET/POST /api/document/types`
- `GET/POST /api/document/definitions`
- `GET/POST /api/document/item-types`
- `GET/POST /api/document/item-definitions`
- Sequence APIs from `/api/sequence/sequences`.

Form fields:

- Document type, definition, source type/id, title, status action, metadata, attachment file, comment body, permission target, relation target.

Table columns:

- Number, type, title, source, status, owner, version, created at, updated at.

Actions:

- Create, update metadata, preview number, change status, upload attachment, add comment, view versions, manage permissions, manage relations.

Backend calculations:

- Numbering, rendering, versioning, workflow status, permission decisions.

Permissions:

- `document.records.view/create/update`, `document.records.status`, `document.definitions.*`, `document.attachments.*`, `document.comments.*`.

Missing backend endpoints:

- Render/download/PDF/email/template preview.
- First-class workflow/step/transition management endpoints if workflows must be edited from UI.

### Finance

Frontend routes:

- `/finance`
- `/finance/accounts`
- `/finance/fiscal-years`
- `/finance/fiscal-periods`
- `/finance/journal-entries`
- `/finance/journal-entries/:id`
- `/finance/ap-transactions`
- `/finance/ar-transactions`
- `/finance/tax`
- `/finance/payment-terms`
- `/finance/cost-centers`
- `/finance/bank-accounts`
- `/finance/bank-transactions`
- `/finance/reconciliations`
- `/finance/budgets`

Screens:

- Chart of Accounts, Fiscal Years, Fiscal Periods, Journal Entries, AP, AR, Tax Groups/Rates/Rules, Payment Terms, Cost Centers, Bank Accounts, Bank Transactions, Reconciliations, Budgets.

APIs:

- CRUD under `/api/finance/accounts`, `/fiscal-years`, `/fiscal-periods`, `/payment-terms`, `/tax-groups`, `/tax-rates`, `/tax-rules`, `/ap-transactions`, `/ar-transactions`, `/cost-centers`, `/journal-entries`, `/journal-entry-lines`, `/budgets`, `/budget-lines`, `/bank-accounts`, `/bank-transactions`, `/bank-reconciliations`, `/bank-category-rules`.
- `POST /api/finance/tax/preview-calculate`
- `POST /api/finance/journal-entries/{journalEntry}/engines/preview-posting`
- Journal post/reverse engine endpoints where registered.

Form fields:

- Account code/name/type/normal balance, fiscal dates, journal date/reference/currency/lines, tax group/rate/rule fields, bank account fields, budget period/lines.

Table columns:

- Code, name, type, date, reference, debit, credit, status, period, currency, created at.

Actions:

- Create/update, preview tax, validate balance, preview posting, post, reverse, lock/unlock if backend added, reconcile.

Backend calculations:

- Double-entry validation, fiscal period locks, journal posting, AP/AR balances, tax, reconciliation totals.

Permissions:

- `finance.accounts.*`, `finance.journals.*`, `finance.journals.post/reverse`, `finance.tax.*`, `finance.banks.*`, `finance.budgets.*`.

Missing backend endpoints:

- Trial balance, ledger report, account statement, period close/reopen, tax reports.

### Inventory

Frontend routes:

- `/inventory`
- `/inventory/stock-levels`
- `/inventory/movements`
- `/inventory/reservations`
- `/inventory/transfers`
- `/inventory/adjustments`
- `/inventory/cycle-counts`
- `/inventory/batches`
- `/inventory/serials`
- `/inventory/inspections`
- `/inventory/put-away`
- `/inventory/picking`
- `/inventory/valuation`
- `/inventory/traceability`

Screens:

- Stock Levels, Stock Movements, Reservations, Transfers, Adjustments, Cycle Counts, Batches, Serials, Receipt Inspections, Put-away Tasks, Picking Tasks, Valuation/Cost Layers, Traceability.

APIs:

- CRUD under `/api/inventory/*` for stock levels, movements, reservations, adjustments, transfers, batches, serials, inspections, tasks, valuation configs, cycle counts.
- `POST /api/inventory/engines/stock-availability/preview`
- Allocation/valuation/dimension engine endpoints where registered.

Form fields:

- Item, UOM, warehouse, location, batch, serial, quantity, source type/id, reason, notes.

Table columns:

- Item, warehouse, location, batch/serial, on hand, reserved, available, movement type, source, date.

Actions:

- Preview availability, reserve, release, transfer, adjust, count, inspect, put away, pick, view trace.

Backend calculations:

- Availability, stock movement, UOM conversion, valuation, cost layers, reservations, reversals.

Permissions:

- `inventory.stock.view`, `inventory.movements.*`, `inventory.reservations.*`, `inventory.transfers.*`, `inventory.adjustments.*`, `inventory.valuation.view`.

Missing backend endpoints:

- Stock ledger report, reservation consume/release shortcuts, batch/serial trace report.

### Payment

Frontend routes:

- `/payments`
- `/payments/payments`
- `/payments/methods`
- `/payments/groups`
- `/payments/allocations`
- `/payments/advances`
- `/payments/refunds`
- `/payments/write-offs`
- `/payments/cash-registers`
- `/payments/checks`

Screens:

- Payments, Payment Methods, Payment Groups, Allocations, Advance Payments, Advance Allocations, Refunds, Write-offs, Cash Registers, Checks/Cheques.

APIs:

- CRUD under `/api/payment/payment-methods`, `/payment-groups`, `/payments`, `/payment-allocations`, `/advance-payments`, `/advance-payment-allocations`, `/cash-registers`, `/checks`, `/write-offs` where registered.
- `POST /api/payment/payments/{payment}/engines/preview-allocation`
- Allocate, unallocate, status, post, reverse, refund engine endpoints.

Form fields:

- Party type/id, source type/id, method, amount, currency, date, reference, bank/check/cash-register details, allocation lines.

Table columns:

- Number, party, method, amount, allocated, unallocated, status, date, reference.

Actions:

- Create, preview allocation, allocate, unallocate, post, reverse, refund, write off, print receipt.

Backend calculations:

- Allocation, settlement, AP/AR impact, payment balances, posting, refunds, reversals.

Permissions:

- `payment.payments.*`, `payment.payments.allocate/post/reverse/refund`, `payment.methods.*`, `payment.cash-registers.*`.

Missing backend endpoints:

- Payment method availability preview, customer/supplier wallet statement.

### Item

Frontend routes:

- `/items`
- `/items/items`
- `/items/items/new`
- `/items/items/:id`
- `/items/categories`
- `/items/types`
- `/items/attributes`
- `/items/variants`
- `/items/combos`
- `/items/identifiers`

Screens:

- Items, Categories, Types, Attributes, Variants, Combo/Bundles, Item Units, Pricing References, Metadata.

APIs:

- `GET/POST /api/item/items`
- `PATCH /api/item/items/{item}/activate`
- `PATCH /api/item/items/{item}/deactivate`
- CRUD for item categories, brands, attributes/groups/values, variants, combo items, identifiers.

Form fields:

- Code/SKU, name, type, category, brand, base UOM, stockable/service flags, tax defaults, units, attributes, variants, combo components, prices, metadata.

Table columns:

- SKU, name, type, category, brand, UOM, stockable, active, updated at.

Actions:

- Create/update, activate/deactivate, add variant, add combo line, attach identifier, open pricing reference.

Backend calculations:

- Combo circular checks, item type rules, UOM validation, nested transaction save.

Permissions:

- `item.items.*`, `item.categories.*`, `item.variants.*`, `item.combos.*`.

Missing backend endpoints:

- Combo expansion preview, item availability, item price/tax defaults.

### UOM

Frontend routes:

- `/uom`
- `/uom/units`
- `/uom/conversions`
- `/uom/convert`

Screens:

- Units, Unit Conversions, Conversion Preview.

APIs:

- CRUD under `/api/uom/unit-of-measures`, `/api/uom/uom-conversions`.
- `POST /api/uom/convert`.

Form fields:

- Unit code/name/category/type, precision, from UOM, to UOM, factor, item-specific flag, quantity.

Table columns:

- Code, name, category, precision, from, to, factor, active.

Actions:

- Create/update unit, create/update conversion, preview conversion.

Backend calculations:

- Conversion, compatibility validation, precision/rounding.

Permissions:

- `uom.units.*`, `uom.conversions.*`, `uom.convert`.

Missing backend endpoints:

- Conversion matrix and item-specific conversion preview.

### Pricing

Frontend routes:

- `/pricing`
- `/pricing/price-lists`
- `/pricing/price-lists/:id`
- `/pricing/items`
- `/pricing/rules`
- `/pricing/rules/:id`
- `/pricing/discounts`
- `/pricing/tiers`
- `/pricing/resolve`
- `/pricing/history`

Screens:

- Price Lists, Price List Items, Pricing Rules, Rule Conditions, Discounts, Discount Rules, Pricing Tiers, Price Resolver Preview, Price History.

APIs:

- `POST /api/pricing/resolve-price`
- `POST /api/pricing/discounts/preview-calculate`
- CRUD under `/api/pricing/price-lists`, `/price-list-items`, `/pricing-rules`, `/pricing-rule-conditions`, `/discounts`, `/discount-rules`, `/pricing-tiers`, `/supplier-price-lists`, `/customer-price-lists`.
- Read-only `/api/pricing/price-histories`.

Form fields:

- Price list name/code/type/scope/currency/date, item/UOM/price, tier breaks, rule conditions, discount type/value, priority, stackable/exclusive flags.

Table columns:

- Code, name, type, scope, item, price, discount, priority, valid from/to, active.

Actions:

- Resolve price, preview discount, create/update list, add item, add tier, add rule, view history.

Backend calculations:

- Price resolution, tier rules, priority, discount logic, currency/UOM/date rules.

Permissions:

- `pricing.price-lists.*`, `pricing.rules.*`, `pricing.discounts.*`, `pricing.resolve`, `pricing.history.view`.

Missing backend endpoints:

- Deeper automatic price-history capture for every pricing mutation.

### Supplier

Frontend routes:

- `/suppliers`
- `/suppliers/new`
- `/suppliers/:id`
- `/suppliers/:id/edit`
- `/suppliers/:id/contacts`
- `/suppliers/:id/addresses`
- `/suppliers/:id/bank-accounts`
- `/suppliers/:id/tax-profile`
- `/suppliers/:id/finance-defaults`
- `/suppliers/:id/user-access`

Screens:

- Suppliers, Supplier Details, Contacts, Addresses, Bank Accounts, Tax Profile, User Access, Finance Defaults.

APIs:

- CRUD suppliers, contacts, addresses, vehicles/items where registered.
- Lookup, status, validate for purchase, finance defaults, tax profile, categories, bank accounts, user access/link/deactivate/unlink.

Form fields:

- Code, name, type, status, contact data, address data, bank account data, tax fields, finance account defaults, optional user link.

Table columns:

- Code, name, category, status, tax number, phone, email, payables, updated at.

Actions:

- Create without user, link existing user, create user access, deactivate access, validate for purchase, update finance defaults.

Backend calculations:

- Supplier validation, optional user linking, finance defaults, payables/aging where available, tenant validation.

Permissions:

- `supplier.suppliers.*`, `supplier.contacts.*`, `supplier.finance-defaults.update`, `supplier.user-access.*`.

Missing backend endpoints:

- Supplier statement, aging/payables shortcut.

### Customer

Frontend routes:

- `/customers`
- `/customers/new`
- `/customers/:id`
- `/customers/:id/edit`
- `/customers/:id/contacts`
- `/customers/:id/addresses`
- `/customers/:id/tax-profile`
- `/customers/:id/credit-profile`
- `/customers/:id/finance-defaults`
- `/customers/:id/user-access`

Screens:

- Customers, Customer Details, Contacts, Addresses, Vehicles, Tax Profile, Credit Profile, User Access, Finance Defaults.

APIs:

- CRUD customers, contacts, addresses, vehicles.
- Lookup, status, validate for sales/vehicle rental/vehicle service, finance defaults, credit check, tax profile, user access/link/deactivate/unlink.

Form fields:

- Code, name, type, status, contacts, addresses, tax fields, credit limit/terms, finance defaults, optional user access.

Table columns:

- Code, name, status, credit limit, outstanding, phone, email, updated at.

Actions:

- Create without user, link existing user, credit check, validate for sale/service/rental, update defaults.

Backend calculations:

- Credit validation, outstanding summary, optional user linking, tenant validation.

Permissions:

- `customer.customers.*`, `customer.credit-check`, `customer.finance-defaults.update`, `customer.user-access.*`.

Missing backend endpoints:

- Customer statement, aging summary, duplicate detection.

### HR

Frontend routes:

- `/hr`
- `/hr/employees`
- `/hr/employees/new`
- `/hr/employees/:id`
- `/hr/departments`
- `/hr/designations`
- `/hr/employment-types`
- `/hr/attendance`
- `/hr/leave`
- `/hr/payroll`
- `/hr/performance`

Screens:

- Employees, Employee Details, Departments, Designations, Contacts, Addresses, Employment Details, User Access, Attendance, Leave, Payroll, Performance.

APIs:

- CRUD departments, designations, employment types, employees, contacts, addresses, documents, contracts, biometric devices, holidays, attendance, shifts, leave, salary, payroll, performance.
- Employee lookup/active/by department/by designation/status/employment details/user access.

Form fields:

- Employee code/name, department, designation, employment type, contacts, addresses, contract, salary profile, attendance/leave inputs, optional user access.

Table columns:

- Code, name, department, designation, employment type, status, joined date, updated at.

Actions:

- Create without user, link user, update employment details, change status, manage contacts/addresses, run payroll when backend preview/finalize exists.

Backend calculations:

- Employee validation, optional user linking, department/designation validation, payroll/leave/attendance calculations.

Permissions:

- `hr.employees.*`, `hr.departments.*`, `hr.attendance.*`, `hr.leave.*`, `hr.payroll.*`.

Missing backend endpoints:

- Payroll calculation preview/finalize, leave approval workflow, attendance import, payslip post-to-finance.

### Tenant And Configuration

Frontend routes:

- `/admin/tenants`
- `/admin/tenants/:id`
- `/admin/configuration`
- `/admin/configuration/entries`
- `/admin/configuration/countries`
- `/admin/configuration/currencies`
- `/admin/configuration/languages`
- `/admin/configuration/timezones`

Screens:

- Tenants, Plans, Domains, Tenant Settings, Documents, Configuration Entries, Features, Countries, Currencies, Languages, Timezones.

APIs:

- Tenant CRUD/lifecycle/settings/domains/documents where registered.
- Configuration entries CRUD/resolve, feature enabled, cache clear, countries/currencies/languages/timezones CRUD.

Form fields:

- Tenant code/name/domain/status/plan, setting key/value/type/scope, feature key, country/currency/language/timezone fields.

Table columns:

- Code, name, status, plan, domain, key, value, scope, active, updated at.

Actions:

- Activate/deactivate/suspend tenant, resolve config, clear cache, update settings.

Backend calculations:

- Tenant isolation, lifecycle, effective settings, config precedence/cache.

Permissions:

- `tenant.*`, `configuration.entries.*`, `configuration.cache.clear`.

Missing backend endpoints:

- Tenant module enablement matrix, provisioning health, configuration schema discovery, bulk import/export.

### Purchase

Frontend routes:

- `/purchase`
- `/purchase/orders`
- `/purchase/orders/new`
- `/purchase/orders/:id`
- `/purchase/grns`
- `/purchase/grns/new`
- `/purchase/grns/:id`
- `/purchase/invoices`
- `/purchase/invoices/new`
- `/purchase/invoices/:id`
- `/purchase/payments`
- `/purchase/advances`
- `/purchase/returns`
- `/purchase/refunds`
- `/purchase/settings`

Screens:

- Purchase Dashboard, Purchase Orders, PO Create/Edit/Details, GRN Create/Edit/Details, Purchase Invoices, Payments, Advance Payments, Returns, Supplier Refunds, Settings.

APIs:

- CRUD purchase orders/lines, GRN headers/lines, purchase returns/lines.
- Purchase invoices CRUD/from PO/from GRN/from multiple GRNs/post/cancel/reverse/lines.
- Purchase payments CRUD/post/void/reverse/allocate/allocations.
- Advances, refunds, write-offs.
- `POST /api/purchase/calculate-invoice`
- `POST /api/purchase/preview-payment-allocation`
- Aggregate with-lines/sync endpoints, lookups, settings, workflow/integration endpoints.

Form fields:

- Supplier, document date, due date, currency, warehouse, source refs, item lines, quantity, UOM, unit price, tax group, discount type/value, notes.

Table columns:

- Number, supplier, date, status, subtotal, tax, discount, grand total, balance, posted, updated at.

Actions:

- Create PO with lines, create GRN from PO, create direct GRN if allowed, create invoice from PO/GRN/multiple GRNs, preview invoice, post/cancel/reverse invoice, make payment, preview/allocate payment, create/allocate advance, create return/refund.

Backend calculations:

- Totals, tax, discount, UOM conversion, inventory receive, AP/journal posting, payment settlement, return effects, rollback.

Permissions:

- `purchase.orders.*`, `purchase.grns.*`, `purchase.invoices.*`, `purchase.invoices.post/reverse`, `purchase.payments.*`, `purchase.returns.*`, `purchase.settings`.

Missing backend endpoints:

- Purchase request/RFQ, first-class supplier invoice resource if separate from current purchase invoice API, landed cost allocation.

### Sales

Frontend routes:

- `/sales`
- `/sales/orders`
- `/sales/orders/new`
- `/sales/orders/:id`
- `/sales/gdns`
- `/sales/gdns/new`
- `/sales/gdns/:id`
- `/sales/invoices`
- `/sales/invoices/new`
- `/sales/invoices/:id`
- `/sales/payments`
- `/sales/advances`
- `/sales/returns`
- `/sales/refunds`
- `/sales/settings`

Screens:

- Sales Dashboard, Sales Orders, SO Create/Edit/Details, GDN Create/Edit/Details, Sales Invoices, Payments, Customer Advances, Returns, Customer Refunds, Settings.

APIs:

- CRUD sales orders/lines, GDN headers/lines, sales returns/lines.
- Sales invoices CRUD/from SO/from GDN/from multiple GDNs/post/cancel/reverse/lines.
- Sales payments CRUD/post/void/reverse/allocate/allocations.
- Advances, refunds, write-offs.
- `POST /api/sales/calculate-invoice`
- `POST /api/sales/preview-payment-allocation`
- `GET /api/sales/stock-availability`
- Aggregate with-lines/sync endpoints, lookups, settings, workflow/integration endpoints.

Form fields:

- Customer, document date, due date, warehouse, currency, item/service lines, quantity, UOM, unit price, tax group, discount type/value, pricing context, notes.

Table columns:

- Number, customer, date, status, subtotal, tax, discount, grand total, balance, stock status, updated at.

Actions:

- Create SO, preview price/stock, create GDN, create invoice from SO/GDN/multiple GDNs, preview invoice, post/cancel/reverse, receive payment, allocate advance, create return/refund.

Backend calculations:

- Pricing, tax/discount, stock reservation/issue, AR/journal/COGS, payment allocation, returns/refunds, UOM conversion.

Permissions:

- `sales.orders.*`, `sales.gdns.*`, `sales.invoices.*`, `sales.invoices.post/reverse`, `sales.payments.*`, `sales.returns.*`, `sales.settings`.

Missing backend endpoints:

- Quotation/proforma, credit note as first-class resource, sales price/tax preview shortcut beyond Pricing/Finance preview APIs.

### Vehicle Service

Frontend routes:

- `/vehicle-service`
- `/vehicle-service/job-cards`
- `/vehicle-service/job-cards/new`
- `/vehicle-service/job-cards/:id`
- `/vehicle-service/job-cards/:id/items`
- `/vehicle-service/job-cards/:id/labour`
- `/vehicle-service/job-cards/:id/parts`
- `/vehicle-service/job-cards/:id/external-services`
- `/vehicle-service/job-cards/:id/customer-supplied`
- `/vehicle-service/job-cards/:id/invoice`
- `/vehicle-service/payments`
- `/vehicle-service/settings`

Screens:

- Service Dashboard, Job Cards, Job Card Create/Edit/Details, Job Items, Labour Assignments, Spare Parts, External Services, Customer-Supplied Items, Service Invoice, Payments, Settings.

APIs:

- CRUD service types, job cards, job card lines, labor items/assignments, non-inventory items, inspections/lines, diagnostics/lines.
- Aggregate job card create/update.
- Sync lines/labor/non-inventory/customer-supplied/external services.
- `POST /api/vehicle-service/job-cards/{jobCardId}/invoice-preview`
- `GET /api/vehicle-service/stock-availability`
- Invoiceable/receivable job cards, settings, status history, workflow and integration endpoints.

Form fields:

- Customer, vehicle, supervisor, odometer, complaint, diagnosis, service lines, spare parts, labor items, employee assignments, non-inventory items, external services, customer-supplied items, tax group, discount type/value.

Table columns:

- Job number, customer, vehicle, supervisor, status, opened date, estimated total, invoice status, payment status.

Actions:

- Create job, add customer/vehicle, assign supervisor, add service/labor/spare/combo items, assign employees, preview stock, sync items, preview invoice, generate invoice, receive payment, close job, post/reverse inventory/finance.

Backend calculations:

- Combo expansion, stock consumption, labor assignment validation, invoice calculation, finance posting, payment allocation.

Permissions:

- `vehicle-service.job-cards.*`, `vehicle-service.job-cards.invoice`, `vehicle-service.job-cards.inventory`, `vehicle-service.payments.*`, `vehicle-service.settings`.

Missing backend endpoints:

- Service appointment/scheduler, combo expansion preview, technician incentive payroll export.

### Vehicle Rental

Frontend routes:

- `/vehicle-rental`
- `/vehicle-rental/availability`
- `/vehicle-rental/agreements`
- `/vehicle-rental/agreements/new`
- `/vehicle-rental/agreements/:id`
- `/vehicle-rental/running-charts`
- `/vehicle-rental/running-charts/:id`
- `/vehicle-rental/invoices`
- `/vehicle-rental/replacements`
- `/vehicle-rental/breakdowns`
- `/vehicle-rental/provider-payables`
- `/vehicle-rental/payments`
- `/vehicle-rental/settings`

Screens:

- Rental Dashboard, Vehicle Availability, Agreements, Agreement Create/Edit, Running Charts, Running Chart Details, Rental Invoice Preview, Rental Invoices, Replacement Vehicles, Breakdowns, Provider Payables, Payments, Settings.

APIs:

- Agreements/running charts CRUD.
- Sync agreement lines, rates, rate rules, extra charges.
- Sync running chart lines.
- `POST /api/vehicle-rental/agreements/{agreementId}/billing-preview`
- `GET /api/vehicle-rental/vehicle-availability`
- Replacements, breakdowns, provider payables, settings, status history, workflow and integration endpoints.

Form fields:

- Customer, vehicle, driver/provider, dates, rate plan, km/hour/day/month terms, overtime/night/weekend/double-rate rules, running chart readings, extra charges, replacement details, breakdown details.

Table columns:

- Agreement number, customer, vehicle, driver/provider, start/end, status, estimated total, billed total, outstanding.

Actions:

- Check availability, create agreement, sync rates/rules/lines, add running chart, preview billing, generate invoice, allocate payment, create provider payable, handle replacement/breakdown, post/reverse finance.

Backend calculations:

- Availability, km/hour/day/month calculations, overtime/night/weekend/double-rate, running chart billing, replacement vehicle logic, customer invoice, provider payable, finance/payment integration.

Permissions:

- `vehicle-rental.agreements.*`, `vehicle-rental.running-charts.*`, `vehicle-rental.billing.preview`, `vehicle-rental.provider-payables.*`, `vehicle-rental.payments.*`, `vehicle-rental.settings`.

Missing backend endpoints:

- Rental quotation, rental calendar by vehicle/driver, damage/refund claim resource, standalone running-chart calculation preview if required separately.

### Voucher

Frontend routes:

- `/vouchers`
- `/vouchers/types`
- `/vouchers/vouchers`
- `/vouchers/vouchers/new`
- `/vouchers/vouchers/:id`
- `/vouchers/vouchers/:id/lines`
- `/vouchers/vouchers/:id/approvals`
- `/vouchers/vouchers/:id/allocations`
- `/vouchers/settings`

Screens:

- Voucher Dashboard, Voucher Types, Vouchers, Voucher Create/Edit, Details, Lines, Approvals, Allocations, Settings.

APIs:

- Voucher types CRUD/activate/deactivate.
- Vouchers CRUD.
- Upsert lines, allocations list/add/update.
- Submit, approve, reject, post, cancel, reverse, history.
- `POST /api/voucher/utilities/preview-number`
- `POST /api/voucher/utilities/validate-balance`
- `POST /api/voucher/utilities/validate-payment-method`
- `GET /api/voucher/utilities/{voucher}/preview-posting`

Form fields:

- Voucher type, date, reference, party/source, debit/credit lines, account, cost center, tax rate, payment method, allocation target, notes.

Table columns:

- Number, type, date, reference, status, total amount, debit, credit, posted, updated at.

Actions:

- Preview number, validate balance, validate payment method, preview posting, submit, approve, reject, post, cancel, reverse, manage allocations.

Backend calculations:

- Debit/credit validation, finance posting, payment integration, approval workflow, reversal.

Permissions:

- `voucher.types.*`, `voucher.vouchers.*`, `voucher.vouchers.submit/approve/reject/post/reverse`, `voucher.allocations.*`.

Missing backend endpoints:

- Voucher templates, recurring vouchers, attachment shortcut.

## 13. Dashboard Widgets

Global dashboard:

- Sales today/month, purchases today/month, open receivables/payables, cash position, low stock, pending approvals, active rentals, open service jobs.

Module dashboards:

- Sales: draft orders, pending deliveries, unpaid invoices, returns, top customers.
- Purchase: pending POs, GRNs awaiting invoice, unpaid supplier documents, returns.
- Vehicle Service: open jobs, delayed jobs, invoiceable jobs, parts shortages.
- Vehicle Rental: available vehicles, active agreements, running charts due, provider payables.
- Finance: journal drafts, unposted vouchers, tax due, bank reconciliation exceptions.
- Inventory: low stock, negative stock warnings, pending transfers, cycle counts.
- Payment: unallocated payments, pending checks, cash register variance.

Widgets must use backend summaries when endpoints exist. Until backend summary endpoints exist, use lightweight list counts only and label them as operational lists, not accounting totals.

## 14. Action/Button Plans

Common list actions:

- New, refresh, export if backend exists, filter, column chooser.

Common row actions:

- View, edit, duplicate where backend supports, delete, activate/deactivate, history.

Workflow actions:

- Submit, approve, reject, post, cancel, reverse, close, reopen only when returned by backend permissions/status or explicitly allowed by endpoint.

Critical action flow:

- Click action.
- Open ConfirmDialog.
- Fetch preview if required.
- Show backend warnings/calculations.
- Submit action.
- Refresh detail, timeline, and related lists.

## 15. Frontend-Only Responsibility Checklist

- Route users through workflows.
- Capture clean input.
- Use selectors for IDs instead of free text where possible.
- Show backend previews.
- Show backend validation errors.
- Show backend status/history/audit.
- Manage drafts locally only before submission.
- Provide good keyboard, search, filter, and responsive UX.
- Never persist or overwrite backend calculated values with local math.

## 16. Backend-Only Logic Checklist

- Tenant/organization validation.
- User permissions and access policy.
- Document numbering/rendering/versioning.
- Workflow transitions and approvals.
- Tax/discount/pricing/UOM conversion.
- Invoice/rental/service totals.
- Stock movements/reservations/valuation.
- Payment allocation/settlement/refunds/write-offs.
- Finance postings/reversals/AP/AR/COGS.
- Payroll/leave/attendance calculations.
- Audit/history capture.

## 17. Implementation Phases

Phase 1: Foundation

- Auth layout, main layout, sidebar/top nav, route guards, API client, query client, permission hooks, tenant/org switcher, shared DataTable/forms/selectors/dialogs.

Phase 2: Master data

- Supplier, Customer, HR employee basics, Item, UOM, Pricing.
- Include optional user access panels for supplier/customer/employee.

Phase 3: Core operational modules

- Document, Finance, Inventory, Payment.
- Include previews for UOM, price, discount, tax, stock availability, payment allocation, finance posting.

Phase 4: Business modules

- Purchase, Sales, VehicleService, VehicleRental, Voucher.
- Build aggregate forms, line editors, preview-confirm-submit flows, workflow panels, payment panels.

Phase 5: Dashboards, reports, polish

- Module dashboards, cross-module search, notifications, saved filters, accessibility pass, test coverage, performance tuning.

## 18. Testing Checklist

Unit tests:

- Permission guards.
- API adapters.
- Validation error mapping.
- Component rendering states.
- Line editor input behavior without calculated math.

Integration tests:

- Auth flow.
- Tenant/org switching.
- List/filter/detail navigation.
- Create/edit forms with backend validation errors.
- Preview-confirm-submit workflows.

End-to-end tests:

- Create customer/supplier/item/UOM/price.
- Sales order to invoice to payment allocation.
- Purchase order to GRN to invoice to payment allocation.
- Vehicle service job to invoice to payment.
- Vehicle rental agreement to running chart to billing preview.
- Voucher submit/approve/post/reverse.

Regression tests:

- Frontend does not send authoritative calculated fields unless backend request explicitly treats them as ignored/display-only.
- Stale preview blocks critical submit where required.
- Permission-hidden actions cannot be triggered through UI.
- Backend validation errors map to correct fields.
- Workflow/history/audit refresh after each critical action.

## 19. First Build Order

1. App shell, auth, context, route guards.
2. Shared API client and response/error normalization.
3. Shared components and selectors.
4. Customer, Supplier, Item, UOM, Pricing.
5. Finance tax preview, Payment allocation preview, Inventory stock preview.
6. Sales and Purchase invoice forms using backend previews.
7. VehicleService and VehicleRental aggregate forms using backend previews.
8. Voucher workflow.
9. Dashboards and reports.
