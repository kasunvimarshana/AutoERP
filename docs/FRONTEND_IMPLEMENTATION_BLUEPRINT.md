# Frontend Implementation Blueprint

Generated from `app/Modules`, `ARCHITECTURE.md`, `PLATFORM_ARCHITECTURE.md`, `application_business_context_requirements.md`, and the current module API audit.

## 1. Blueprint Goal

Design a modular frontend that mirrors the backend business-capability architecture.

The frontend must:

- be module-aware, tenant-aware, permission-aware, and preview-driven
- collect user input, call backend APIs, display backend results, and submit confirmed records
- never own business-critical calculations
- remain scalable enough for new business modules without reworking the shell

The backend must remain authoritative for:

- invoice totals
- taxes
- discounts
- finance postings
- stock movements
- payment balances and allocations
- UOM conversions
- pricing rules
- workflow status transitions
- returns, refunds, reversals
- rental running chart billing
- vehicle service invoice totals

## 2. Recommended Frontend Stack

- Framework: React + TypeScript
- App shell: Vite
- Routing: React Router with route modules
- Server state: TanStack Query
- Local form state: React Hook Form + Zod for client-side UX validation only
- Grid/table: reusable internal `DataTable` built on TanStack Table
- UI primitives: shared internal component library
- Auth/session: token/session manager aligned to Auth module responses
- Permissions: route-level and component-level guards using backend permission codes
- File uploads: shared upload adapter for Document and Extension attachment flows
- Testing: Vitest, React Testing Library, Playwright

## 3. Frontend Architecture Rules

- Module ownership in frontend must match backend module ownership.
- Shared entities such as `Customer`, `Supplier`, `Item`, `UOM`, `Pricing`, `Document`, `Payment`, `Finance`, and `Inventory` must be consumed through their APIs, not reimplemented locally.
- All create/edit flows that affect totals, balances, stock, posting, or workflow must use backend preview endpoints before final confirmation.
- Frontend-calculated values may exist only for temporary UX feedback such as line ordering, empty-state hints, or unsaved draft indicators. They must never be treated as authoritative business values.
- Tenant identity comes from auth/session context and backend middleware. Frontend must not treat tenant id in payload as a trust boundary.
- Each module page must support audit/history visibility where the backend exposes it.

## 4. App Folder Structure

```text
src/
  app/
    providers/
    router/
    store/
    layouts/
      AuthLayout.tsx
      AppLayout.tsx
      ModuleWorkspaceLayout.tsx
    guards/
      AuthGuard.tsx
      PermissionGuard.tsx
      TenantGuard.tsx
  core/
    api/
      client.ts
      queryKeys.ts
      errorMapper.ts
      pagination.ts
    auth/
    permissions/
    session/
    navigation/
    utils/
    types/
  components/
    data-display/
    forms/
    feedback/
    overlays/
    document/
    workflow/
    audit/
    attachments/
    selectors/
    finance/
    inventory/
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
  hooks/
  styles/
  test/
```

Per module structure:

```text
modules/<module>/
  routes.tsx
  api/
  pages/
  components/
  forms/
  tables/
  hooks/
  permissions.ts
  types.ts
```

## 5. Route Map

Top-level frontend routes:

- `/login`
- `/register`
- `/verify`
- `/app`
- `/app/dashboard`
- `/app/document/*`
- `/app/finance/*`
- `/app/inventory/*`
- `/app/payment/*`
- `/app/item/*`
- `/app/uom/*`
- `/app/pricing/*`
- `/app/supplier/*`
- `/app/customer/*`
- `/app/hr/*`
- `/app/tenant/*`
- `/app/configuration/*`
- `/app/purchase/*`
- `/app/sales/*`
- `/app/vehicle-service/*`
- `/app/vehicle-rental/*`
- `/app/voucher/*`
- `/app/audit`
- `/app/settings`

Recommended route pattern per entity:

- list: `/app/<module>/<resource>`
- create: `/app/<module>/<resource>/new`
- detail: `/app/<module>/<resource>/:id`
- edit: `/app/<module>/<resource>/:id/edit`
- preview or workflow panel: `/app/<module>/<resource>/:id/<panel>`

## 6. Navigation And Menu Structure

Primary sidebar groups:

- Dashboard
- Operations
- Masters
- Finance
- Mobility
- Administration

Sidebar menu map:

- Dashboard
- Operations: Purchase, Sales, Inventory, Payment, Voucher
- Mobility: Vehicle Service, Vehicle Rental
- Masters: Item, UOM, Pricing, Customer, Supplier, HR
- Finance: Finance, Document
- Administration: Tenant, Configuration, Users, Audit

Top navigation:

- tenant switcher
- organization unit switcher
- global search
- quick create menu
- notifications
- current workflow inbox
- user profile menu

Quick create menu:

- Purchase Order
- GRN
- Purchase Invoice
- Sales Order
- Sales Invoice
- Payment
- Job Card
- Rental Agreement
- Voucher
- Customer
- Supplier
- Item

## 7. Shared Page Patterns

- Dashboard page
- List page with filters and saved views
- Create/edit workspace with sticky action bar
- Detail page with tabs
- Workflow panel
- Audit/history panel
- Attachment/comment panel
- Document preview panel
- Related records panel
- Settings page

Detail page tab pattern:

- Summary
- Lines or details
- Financials
- Workflow
- Documents
- Attachments
- Comments
- Audit

## 8. Reusable Components

Core reusable components:

- `DataTable`
- `SearchFilterBar`
- `StatusBadge`
- `ActionDropdown`
- `ConfirmDialog`
- `FormSection`
- `DynamicFormRenderer`
- `EmptyState`
- `PageHeader`
- `StickyActionBar`
- `EntitySummaryCard`
- `EntityMetaPanel`
- `ErrorSummary`
- `PermissionGate`

Document and workflow components:

- `DocumentPreview`
- `WorkflowTimeline`
- `WorkflowActionPanel`
- `AuditTimeline`
- `AttachmentManager`
- `CommentPanel`
- `RelatedRecordList`

Business input components:

- `MoneyInput`
- `QuantityInput`
- `PercentageInput`
- `UomSelector`
- `ItemSelector`
- `CustomerSelector`
- `SupplierSelector`
- `EmployeeSelector`
- `VehicleSelector`
- `TaxGroupSelector`
- `WarehouseSelector`
- `LocationSelector`
- `PaymentMethodSelector`
- `BankAccountSelector`

Business output components:

- `PaymentAllocationTable`
- `TaxBreakdownPanel`
- `DiscountPanel`
- `StockAvailabilityIndicator`
- `PricePreviewPanel`
- `PostingPreviewPanel`
- `BalanceSummaryPanel`
- `RunningChartPreviewPanel`
- `ServiceCostPreviewPanel`

Profile subforms:

- `AddressForm`
- `ContactForm`
- `BankAccountForm`
- `TaxProfileForm`
- `CreditProfileForm`
- `UserAccessForm`

## 9. State Management Approach

- Use TanStack Query for all server state.
- Use React Hook Form for local form state.
- Use lightweight local stores only for shell concerns such as sidebar state, current tenant context snapshot, draft UI preferences, and unsaved wizard step state.
- Never keep authoritative balances, totals, or posting values in client-side global stores.
- Keep query keys module-scoped. Example: `['purchase', 'orders', filters]`.
- Use optimistic UI only for non-critical UX actions such as pinning filters, expanding panels, or updating local notes after backend acknowledgement.
- For critical writes, invalidate and re-fetch detail and related preview queries after mutation success.

## 10. Permissions Model

Permission dimensions:

- module access
- entity view
- entity create
- entity update
- entity delete
- entity approve
- entity post
- entity reverse
- entity refund
- entity preview
- attachment access
- comment access
- audit access
- settings access

Permission map by module:

- Document: `document.view`, `document.manage`, `document.workflow.manage`, `document.preview`
- Finance: `finance.view`, `finance.journal.post`, `finance.tax.manage`, `finance.bank.reconcile`
- Inventory: `inventory.view`, `inventory.adjust`, `inventory.transfer`, `inventory.count`, `inventory.preview`
- Payment: `payment.view`, `payment.create`, `payment.allocate`, `payment.refund`, `payment.reverse`, `payment.preview`
- Item: `item.view`, `item.create`, `item.update`
- UOM: `uom.view`, `uom.manage`, `uom.preview`
- Pricing: `pricing.view`, `pricing.manage`, `pricing.preview`
- Supplier: `supplier.view`, `supplier.create`, `supplier.update`, `supplier.finance.manage`
- Customer: `customer.view`, `customer.create`, `customer.update`, `customer.credit.manage`
- HR: `hr.view`, `hr.manage`, `hr.payroll.manage`
- Tenant: `tenant.view`, `tenant.manage`, `tenant.lifecycle.manage`
- Configuration: `configuration.view`, `configuration.manage`
- Purchase: `purchase.view`, `purchase.create`, `purchase.approve`, `purchase.receive`, `purchase.invoice`, `purchase.pay`, `purchase.return`
- Sales: `sales.view`, `sales.create`, `sales.approve`, `sales.deliver`, `sales.invoice`, `sales.receive-payment`, `sales.return`
- VehicleService: `vehicle-service.view`, `vehicle-service.create`, `vehicle-service.invoice`, `vehicle-service.receive-payment`, `vehicle-service.close`
- VehicleRental: `vehicle-rental.view`, `vehicle-rental.create`, `vehicle-rental.bill`, `vehicle-rental.receive-payment`, `vehicle-rental.close`
- Voucher: `voucher.view`, `voucher.create`, `voucher.approve`, `voucher.post`, `voucher.reverse`

Frontend permission behavior:

- route guards hide inaccessible modules
- action buttons render only for allowed transitions
- tables can hide sensitive columns such as costs or margins based on permissions
- audit and attachments can be independently permissioned

## 11. Validation UX Rules

- Validate required fields, type format, and obvious client-side shape before submit.
- Use backend responses as the authoritative source for business validation.
- Show inline field errors, section summaries, and sticky error banners for long forms.
- Keep preview panels visible after validation failures so users can correct inputs without losing context.
- For line-heavy documents, highlight the exact row and field returned by backend validation.
- Treat backend business warnings separately from blocking errors.

Validation UX patterns:

- immediate validation for required ids, dates, quantity format, email, phone, and file type
- deferred validation for submit-only business constraints
- preview refresh after key inputs change: party, item, quantity, UOM, tax group, warehouse, rental period, service lines

## 12. Backend Preview Endpoint Checklist

Required preview endpoints and frontend usage:

- Purchase invoice calculation preview: use before saving or posting purchase invoice
- Sales invoice calculation preview: use before saving or posting sales invoice
- Vehicle service invoice preview: use before invoice generation and before close
- Vehicle rental invoice preview: use before invoice generation and before provider payable confirmation
- Payment allocation preview: use before confirming customer, supplier, or rental/service allocations
- Advance allocation preview: use before applying advances to invoices or agreements
- Stock availability preview: use when selecting warehouse, location, item, serial, or source document lines
- UOM conversion preview: use when quantity/UOM is changed
- Price resolving preview: use whenever item, customer, supplier, quantity, date, or UOM changes
- Tax or discount calculation preview: use for invoice-like lines before submit
- Finance posting preview: use before posting journals, vouchers, and document-linked postings
- Rental running chart calculation preview: use while editing running chart or billing inputs

Current backend coverage from the audit:

- `POST /api/purchase/calculate-invoice`
- `POST /api/sales/calculate-invoice`
- `POST /api/payment/payments/{payment}/engines/preview-allocation`
- `POST /api/inventory/engines/stock-availability/preview`
- `POST /api/uom/convert`
- `POST /api/pricing/resolve-price`
- `POST /api/finance/tax/preview-calculate`
- `POST /api/finance/journal-entries/{journalEntry}/engines/preview-posting`
- Vehicle service and rental preview flows exist through management and workflow endpoints

Missing or still weak backend preview coverage:

- standalone discount preview endpoint
- explicit vehicle service invoice preview endpoint naming
- explicit vehicle rental running chart preview endpoint naming
- purchase landed-cost preview if landed cost becomes a first-class flow

## 13. Backend-Only Logic Checklist

- document numbering
- workflow transitions
- approval workflows
- document rendering and versioning
- price list resolution
- tax calculation
- discount calculation
- invoice totals
- payment allocation and settlement
- bank reconciliation math
- stock reservation and movement
- stock valuation and cost layers
- UOM conversion and rounding
- combo item expansion
- service item and labour validation
- rental running chart billing
- provider payable generation
- finance posting
- reversal and refund effects
- tenant isolation checks
- audit and history capture

## 14. Frontend-Only Responsibility Checklist

- routing and workspace composition
- session persistence and logout UX
- filter state and saved views
- table display and column controls
- collecting form input
- autosave of non-authoritative drafts if desired
- triggering preview calls
- rendering backend previews
- rendering validation messages
- showing audit, attachments, comments, and related records
- managing navigation and breadcrumbs
- respecting permissions in the UI

## 15. Module Blueprint

### 15.1 Document

Screens:

- Document Definitions
- Document Templates
- Document Types
- Document Sequences
- Document Workflows
- Workflow Steps
- Workflow Transitions
- Document Records
- Document Preview
- Document History
- Attachments and Comments

Frontend routes:

- `/app/document/definitions`
- `/app/document/templates`
- `/app/document/types`
- `/app/document/sequences`
- `/app/document/workflows`
- `/app/document/records`
- `/app/document/records/:id`
- `/app/document/records/:id/preview`
- `/app/document/records/:id/history`

Required APIs:

- definitions CRUD
- templates CRUD
- types CRUD
- sequences list and preview
- workflows, steps, and transitions CRUD
- document records list and detail
- attachments, comments, events, permissions, relations

Forms:

- definition: code, name, module, model, active
- template: type, name, engine, content, active
- sequence: prefix, suffix, padding, reset rule, next-number preview
- workflow: document type, step list, transition rules, active

Tables:

- definitions: code, name, module, type count, active
- templates: name, type, version, active, updated at
- sequences: code, prefix, next number, reset rule, active
- records: number, type, source module, status, created by, created at

Actions:

- create, edit, activate, deactivate
- preview document
- view version history
- manage workflow
- preview next sequence

Backend calculations needed:

- numbering preview
- rendered data preview
- allowed transitions

Frontend responsibilities:

- manage setup screens
- preview backend-rendered documents
- show workflow timelines and attachments

Permissions:

- `document.view`
- `document.manage`
- `document.workflow.manage`
- `document.preview`

Missing backend endpoints:

- explicit render or download endpoint naming
- template preview endpoint
- PDF or email dispatch endpoints if not already available behind generic document routes

### 15.2 Finance

Screens:

- Chart of Accounts
- Fiscal Years
- Fiscal Periods
- Journal Entries
- AP Transactions
- AR Transactions
- Tax Groups, Rates, Rules
- Payment Terms
- Cost Centers
- Bank Accounts
- Bank Transactions
- Reconciliations
- Budgets

Frontend routes:

- `/app/finance/accounts`
- `/app/finance/fiscal-years`
- `/app/finance/fiscal-periods`
- `/app/finance/journal-entries`
- `/app/finance/journal-entries/:id`
- `/app/finance/ap-transactions`
- `/app/finance/ar-transactions`
- `/app/finance/tax`
- `/app/finance/payment-terms`
- `/app/finance/cost-centers`
- `/app/finance/bank-accounts`
- `/app/finance/bank-transactions`
- `/app/finance/reconciliations`
- `/app/finance/budgets`

Required APIs:

- account CRUD
- fiscal year and period CRUD
- journal entry and line CRUD
- posting preview, post, reverse
- AP and AR transaction CRUD and lookup
- tax group, rate, rule CRUD and preview calculation
- payment terms CRUD
- cost center CRUD
- bank account and bank transaction CRUD
- reconciliation workflows
- budget CRUD

Forms:

- journal: date, reference, memo, currency, lines, cost centers
- tax: code, type, rate, rule conditions, active
- bank reconciliation: bank account, statement date, lines, matched items

Tables:

- accounts: code, name, type, parent, active
- periods: fiscal year, start, end, status
- journals: number, date, reference, status, debit total, credit total
- AP or AR: document number, party, due date, outstanding amount, status
- bank transactions: date, reference, amount, matched status

Actions:

- preview posting
- post journal
- reverse journal
- preview tax
- reconcile bank statement

Backend calculations needed:

- balanced entries
- tax preview
- reconciliation totals
- outstanding balances

Frontend responsibilities:

- entry forms
- posting preview display
- reconciliation workspace

Permissions:

- `finance.view`
- `finance.journal.post`
- `finance.tax.manage`
- `finance.bank.reconcile`

Missing backend endpoints:

- trial balance
- general ledger
- tax reports
- period close and reopen

### 15.3 Inventory

Screens:

- Stock Levels
- Stock Movements
- Stock Reservations
- Stock Transfers
- Stock Adjustments
- Cycle Counts
- Batches
- Serials
- Receipt Inspections
- Put-away Tasks
- Picking Tasks
- Valuation and Cost Layers
- Traceability

Frontend routes:

- `/app/inventory/stock-levels`
- `/app/inventory/movements`
- `/app/inventory/reservations`
- `/app/inventory/transfers`
- `/app/inventory/adjustments`
- `/app/inventory/cycle-counts`
- `/app/inventory/batches`
- `/app/inventory/serials`
- `/app/inventory/inspections`
- `/app/inventory/put-away`
- `/app/inventory/picking`
- `/app/inventory/valuation`
- `/app/inventory/traceability`

Required APIs:

- stock, movement, reservation, transfer, adjustment CRUD or actions
- cycle count flows
- batch and serial lookups
- inspection, picking, put-away flows
- valuation and traceability reads
- stock availability preview

Forms:

- transfer: from warehouse, to warehouse, lines, reason
- adjustment: warehouse, item, quantity, UOM, reason, batch or serial
- cycle count: warehouse, scope, counted lines

Tables:

- stock levels: item, warehouse, location, available, reserved, on hand, UOM
- movements: date, movement type, item, warehouse, quantity, cost impact
- transfers: number, source, destination, status, created at
- cycle counts: count no, warehouse, status, variance lines

Actions:

- preview availability
- reserve stock
- release stock
- confirm transfer
- post adjustment
- finalize cycle count
- trace item

Backend calculations needed:

- availability
- conversions
- valuation
- reservation effects

Frontend responsibilities:

- scan and select stock references
- show backend availability and traceability

Permissions:

- `inventory.view`
- `inventory.adjust`
- `inventory.transfer`
- `inventory.count`
- `inventory.preview`

Missing backend endpoints:

- stock ledger report
- reservation consume or release shortcuts
- richer traceability summary endpoints if current responses are low-level

### 15.4 Payment

Screens:

- Payments
- Payment Methods
- Payment Groups
- Payment Allocations
- Advance Payments
- Advance Allocations
- Refunds
- Write-offs
- Cash Registers
- Checks or Cheques

Frontend routes:

- `/app/payment/payments`
- `/app/payment/payments/:id`
- `/app/payment/methods`
- `/app/payment/groups`
- `/app/payment/allocations`
- `/app/payment/advances`
- `/app/payment/refunds`
- `/app/payment/write-offs`
- `/app/payment/cash-registers`
- `/app/payment/checks`

Required APIs:

- payment methods and groups CRUD
- payments CRUD and workflow actions
- allocation preview and confirm
- advances and advance allocations
- refunds and write-offs
- checks and cash register flows

Forms:

- payment: party, source module, method, amount, currency, bank or register, notes
- allocation: payment id, candidate documents, selected lines, requested amounts
- refund: source payment, reason, amount, method

Tables:

- payments: number, party, source type, amount, allocated, balance, status, date
- allocations: payment, target document, amount, status
- advances: number, party, amount, allocated, remaining

Actions:

- preview allocation
- allocate
- unallocate
- post payment
- reverse payment
- create refund
- write off balance

Backend calculations needed:

- allocation breakdown
- remaining balance
- settlement effects

Frontend responsibilities:

- drive payment workflows
- show allocation preview and settlement results

Permissions:

- `payment.view`
- `payment.create`
- `payment.allocate`
- `payment.refund`
- `payment.reverse`
- `payment.preview`

Missing backend endpoints:

- wallet or statement views
- explicit advance preview endpoint if current flow is embedded only in integration actions

### 15.5 Item

Screens:

- Items
- Item Categories
- Item Types
- Item Attributes
- Item Variants
- Combo or Bundles
- Item Units
- Item Pricing References
- Item Metadata

Frontend routes:

- `/app/item/items`
- `/app/item/items/new`
- `/app/item/items/:id`
- `/app/item/categories`
- `/app/item/types`
- `/app/item/attributes`
- `/app/item/variants`
- `/app/item/combos`

Required APIs:

- item CRUD
- categories, types, attributes, values CRUD
- variants CRUD
- combo items CRUD
- identifiers and metadata CRUD

Forms:

- item master: code, name, type, stockable, default UOM, category, tax defaults
- variants: option sets, SKU, barcode
- combo: parent item, component item, quantity, UOM, optional flag

Tables:

- items: code, name, type, category, default UOM, active
- variants: parent item, option summary, SKU, active
- combos: parent, component, qty, UOM

Actions:

- create item
- add nested units, variants, attributes, combo items, metadata
- activate or deactivate item

Backend calculations needed:

- combo expansion preview
- UOM compatibility for item units

Frontend responsibilities:

- nested form handling
- item reference selection

Permissions:

- `item.view`
- `item.create`
- `item.update`

Missing backend endpoints:

- combo expansion preview
- item availability summary endpoint

### 15.6 UOM

Screens:

- UOM Categories
- Units
- Unit Conversions
- Conversion Preview

Frontend routes:

- `/app/uom/categories`
- `/app/uom/units`
- `/app/uom/conversions`
- `/app/uom/preview`

Required APIs:

- categories CRUD
- units CRUD
- conversions CRUD
- conversion preview

Forms:

- unit: code, name, symbol, category, precision
- conversion: from unit, to unit, factor, rounding mode, active

Tables:

- units: code, name, category, precision, active
- conversions: from, to, factor, rounding, active

Actions:

- preview conversion
- create conversion
- edit precision

Backend calculations needed:

- converted quantity
- rounding result

Frontend responsibilities:

- request and display conversion results only

Permissions:

- `uom.view`
- `uom.manage`
- `uom.preview`

Missing backend endpoints:

- conversion matrix or compatibility summary

### 15.7 Pricing

Screens:

- Price Lists
- Price List Items
- Pricing Rules
- Rule Conditions
- Discounts
- Discount Rules
- Pricing Tiers
- Price Resolver Preview
- Price Histories

Frontend routes:

- `/app/pricing/price-lists`
- `/app/pricing/price-lists/:id`
- `/app/pricing/rules`
- `/app/pricing/discounts`
- `/app/pricing/tiers`
- `/app/pricing/resolve`
- `/app/pricing/history`

Required APIs:

- price list CRUD
- price list item CRUD
- pricing rule CRUD
- rule condition CRUD
- discount CRUD
- discount rule CRUD
- pricing tier CRUD
- resolve price
- price history list

Forms:

- price list: code, name, currency, active dates, priority
- price item: item, UOM, min qty, unit price, currency
- pricing rule: scope, party or item filters, date filters, priority
- discount: type, value, cap, active dates

Tables:

- price lists: code, name, currency, priority, active
- price items: item, UOM, min qty, unit price, effective dates
- rules: name, scope, priority, active
- discounts: name, type, value, active
- history: source, item, old price, new price, changed at

Actions:

- resolve price preview
- create and activate price structures
- inspect price history

Backend calculations needed:

- effective unit price
- discount breakdown
- UOM normalization

Frontend responsibilities:

- call `resolve-price`
- display breakdown and selected rule

Permissions:

- `pricing.view`
- `pricing.manage`
- `pricing.preview`

Missing backend endpoints:

- standalone discount preview
- richer history capture by mutation source

### 15.8 Supplier

Screens:

- Suppliers
- Supplier Details
- Supplier Contacts
- Supplier Addresses
- Supplier Bank Accounts
- Supplier Tax Profile
- Supplier User Access
- Supplier Finance Defaults

Frontend routes:

- `/app/supplier/suppliers`
- `/app/supplier/suppliers/new`
- `/app/supplier/suppliers/:id`
- `/app/supplier/suppliers/:id/edit`

Required APIs:

- supplier CRUD
- contacts CRUD
- addresses CRUD
- bank accounts CRUD
- tax profile get or update
- finance defaults get or update
- optional user access link or unlink
- validation or lookup endpoints

Forms:

- supplier master: code, name, type, status
- contacts: name, role, phone, email
- addresses: billing, shipping, tax address
- finance defaults: payment terms, AP account, tax group
- optional user access: linked user id or invitation

Tables:

- suppliers: code, name, type, tax id, status
- contacts: name, role, email, phone
- bank accounts: bank, account no, currency, default

Actions:

- create supplier without user
- optionally link user
- update finance defaults
- validate supplier for purchasing

Backend calculations needed:

- none beyond validation summaries

Frontend responsibilities:

- profile management
- optional user access management

Permissions:

- `supplier.view`
- `supplier.create`
- `supplier.update`
- `supplier.finance.manage`

Missing backend endpoints:

- supplier statement
- supplier aging

### 15.9 Customer

Screens:

- Customers
- Customer Details
- Customer Contacts
- Customer Addresses
- Customer Tax Profile
- Customer Credit Profile
- Customer User Access
- Customer Finance Defaults

Frontend routes:

- `/app/customer/customers`
- `/app/customer/customers/new`
- `/app/customer/customers/:id`
- `/app/customer/customers/:id/edit`

Required APIs:

- customer CRUD
- contacts CRUD
- addresses CRUD
- tax profile get or update
- credit profile get or update
- finance defaults get or update
- optional user access link or unlink
- validation and credit check endpoints

Forms:

- customer master: code, name, category, status
- contacts and addresses
- tax profile
- credit profile: limit, terms, hold flag
- optional user access

Tables:

- customers: code, name, category, credit status, outstanding, status
- contacts: name, role, email, phone
- addresses: type, city, country, default

Actions:

- create customer without user
- optionally link user
- check credit
- validate customer for sales, service, rental

Backend calculations needed:

- outstanding summary
- credit exposure

Frontend responsibilities:

- customer profile and credit UI

Permissions:

- `customer.view`
- `customer.create`
- `customer.update`
- `customer.credit.manage`

Missing backend endpoints:

- customer statement
- customer aging
- duplicate detection

### 15.10 HR

Screens:

- Employees
- Employee Details
- Departments
- Designations
- Employee Contacts
- Employee Addresses
- Employment Details
- Employee User Access

Frontend routes:

- `/app/hr/employees`
- `/app/hr/employees/new`
- `/app/hr/employees/:id`
- `/app/hr/departments`
- `/app/hr/designations`

Required APIs:

- employee CRUD
- departments CRUD
- designations CRUD
- contact and address CRUD
- employment details CRUD
- optional user access link or unlink
- attendance, leave, salary, payroll APIs where exposed

Forms:

- employee master: code, name, department, designation, joining date, active
- contacts and addresses
- employment details: supervisor, grade, contract type
- optional user access

Tables:

- employees: code, name, department, designation, active
- departments: code, name, manager
- designations: code, name, level

Actions:

- create employee without user
- optionally link user
- maintain organization data

Backend calculations needed:

- payroll preview and posting when implemented
- leave balance when implemented

Frontend responsibilities:

- employee master and HR setup

Permissions:

- `hr.view`
- `hr.manage`
- `hr.payroll.manage`

Missing backend endpoints:

- payroll preview and finalize
- leave approval workflows
- richer HR dashboards

### 15.11 Tenant

Screens:

- Tenants
- Tenant Details
- Domains
- Plans
- Tenant Settings
- Lifecycle
- Tenant Documents

Frontend routes:

- `/app/tenant/tenants`
- `/app/tenant/tenants/:id`
- `/app/tenant/domains`
- `/app/tenant/plans`
- `/app/tenant/settings`

Required APIs:

- tenant CRUD
- domains CRUD
- plans CRUD or assignment
- tenant settings
- lifecycle actions
- tenant documents

Forms:

- tenant: code, name, status, plan
- domain: hostname, primary flag
- settings: locale, currency, timezone, feature set

Tables:

- tenants: code, name, plan, status, created at
- domains: host, primary, status

Actions:

- create tenant
- change plan
- activate, suspend, or archive

Backend calculations needed:

- provisioning readiness summaries

Frontend responsibilities:

- tenant admin UX

Permissions:

- `tenant.view`
- `tenant.manage`
- `tenant.lifecycle.manage`

Missing backend endpoints:

- module enablement summary
- provisioning health

### 15.12 Configuration

Screens:

- Settings
- Feature Flags
- Countries
- Currencies
- Languages
- Timezones

Frontend routes:

- `/app/configuration/settings`
- `/app/configuration/features`
- `/app/configuration/countries`
- `/app/configuration/currencies`
- `/app/configuration/languages`
- `/app/configuration/timezones`

Required APIs:

- config entry CRUD
- setting resolution
- feature enablement checks
- reference-data CRUD

Forms:

- setting: key, module, scope, value, value type, active
- feature flag: code, scope, enabled, rollout notes

Tables:

- settings: key, module, scope, value type, updated at
- features: code, scope, enabled, updated at

Actions:

- edit settings
- resolve setting
- clear cache if permitted

Backend calculations needed:

- effective setting resolution

Frontend responsibilities:

- settings admin and display of resolved values

Permissions:

- `configuration.view`
- `configuration.manage`

Missing backend endpoints:

- schema discovery
- bulk import and export

### 15.13 Purchase

Screens:

- Purchase Dashboard
- Purchase Orders
- Purchase Order Create or Edit
- Purchase Order Details
- GRN
- GRN Create or Edit
- GRN Details
- Purchase Invoices
- Purchase Invoice Create or Edit
- Purchase Invoice Details
- Purchase Payments
- Advance Payments
- Purchase Returns
- Supplier Refunds
- Purchase Settings

Frontend routes:

- `/app/purchase/dashboard`
- `/app/purchase/orders`
- `/app/purchase/orders/new`
- `/app/purchase/orders/:id`
- `/app/purchase/grn`
- `/app/purchase/grn/new`
- `/app/purchase/grn/:id`
- `/app/purchase/invoices`
- `/app/purchase/invoices/new`
- `/app/purchase/invoices/:id`
- `/app/purchase/payments`
- `/app/purchase/advances`
- `/app/purchase/returns`
- `/app/purchase/refunds`
- `/app/purchase/settings`

Required APIs:

- PO, line, GRN, GRN line, return, return line CRUD
- purchase invoice calculation preview
- workflow actions
- integration actions for documents, payments, advances, refunds, reverse
- supplier payable lookup
- payment allocation preview
- settings endpoints

Forms:

- PO: supplier, dates, warehouse, lines, notes
- GRN: source PO or direct, warehouse, received lines, inspection status
- invoice: supplier, source docs, charges, tax group, discount input, lines
- payment: invoice refs, amount, method
- return: source invoice or GRN, lines, reason

Tables:

- orders: number, supplier, date, status, total, received status
- GRN: number, supplier, warehouse, status, received at
- invoices: number, supplier, due date, total, balance, status
- returns: number, supplier, source, amount, status

Actions:

- create PO with lines
- create GRN from PO
- create direct GRN
- create invoice from PO, GRN, or multiple GRNs
- preview invoice calculation
- post invoice
- make payment
- preview payment allocation
- create advance
- allocate advance
- create return
- create refund
- cancel or reverse

Backend calculations needed:

- tax
- discount
- totals
- received quantity effects
- AP posting preview
- payment allocation

Frontend responsibilities:

- collect purchasing inputs
- orchestrate preview-confirm-submit flow
- show status, history, and related docs

Permissions:

- `purchase.view`
- `purchase.create`
- `purchase.approve`
- `purchase.receive`
- `purchase.invoice`
- `purchase.pay`
- `purchase.return`

Missing backend endpoints:

- purchase request or RFQ
- first-class supplier invoice CRUD if current invoice flow is only embedded
- landed cost allocation and preview

### 15.14 Sales

Screens:

- Sales Dashboard
- Sales Orders
- Sales Order Create or Edit
- Sales Order Details
- GDN
- GDN Create or Edit
- Sales Invoices
- Sales Invoice Create or Edit
- Sales Invoice Details
- Sales Payments
- Customer Advances
- Sales Returns
- Customer Refunds
- Sales Settings

Frontend routes:

- `/app/sales/dashboard`
- `/app/sales/orders`
- `/app/sales/orders/new`
- `/app/sales/orders/:id`
- `/app/sales/gdn`
- `/app/sales/gdn/new`
- `/app/sales/gdn/:id`
- `/app/sales/invoices`
- `/app/sales/invoices/new`
- `/app/sales/invoices/:id`
- `/app/sales/payments`
- `/app/sales/advances`
- `/app/sales/returns`
- `/app/sales/refunds`
- `/app/sales/settings`

Required APIs:

- sales order, line, GDN, return CRUD
- sales invoice calculation preview
- workflow actions
- integration actions for document, stock, finance, payment, reverse, refund
- stock availability lookup
- payment and advance allocation previews
- settings endpoints

Forms:

- order: customer, dates, warehouse, price context, lines
- GDN: source order, warehouse, delivery lines
- invoice: customer, source docs, item lines, tax or discount inputs
- payment and refund forms
- return: source invoice or delivery, reason, lines

Tables:

- orders: number, customer, date, status, total
- GDN: number, customer, status, delivered at
- invoices: number, customer, due date, total, balance, status
- returns: number, customer, source, amount, status

Actions:

- create order
- reserve stock
- create GDN
- create invoice
- preview invoice calculation
- post invoice
- receive payment
- preview payment allocation
- allocate advance
- create return
- create refund
- reverse document

Backend calculations needed:

- price resolving
- tax and discount
- totals
- stock reservation and issue
- AR posting and COGS preview

Frontend responsibilities:

- gather sales inputs
- show backend-calculated totals and stock status

Permissions:

- `sales.view`
- `sales.create`
- `sales.approve`
- `sales.deliver`
- `sales.invoice`
- `sales.receive-payment`
- `sales.return`

Missing backend endpoints:

- quotations
- proforma invoices
- first-class credit note surface if currently embedded only in return flows

### 15.15 Vehicle Service

Screens:

- Service Dashboard
- Job Cards
- Job Card Create or Edit
- Job Card Details
- Job Items
- Labour Assignments
- Spare Parts
- External Services
- Customer-Supplied Items
- Service Invoice
- Service Payments
- Service Settings

Frontend routes:

- `/app/vehicle-service/dashboard`
- `/app/vehicle-service/job-cards`
- `/app/vehicle-service/job-cards/new`
- `/app/vehicle-service/job-cards/:id`
- `/app/vehicle-service/job-cards/:id/labour`
- `/app/vehicle-service/job-cards/:id/spares`
- `/app/vehicle-service/invoices`
- `/app/vehicle-service/payments`
- `/app/vehicle-service/settings`

Required APIs:

- job card aggregate CRUD
- labour, spare, external service, customer-supplied item sync endpoints
- stock availability lookups
- service invoice preview or management actions
- payment actions
- workflow and status history
- settings endpoints

Forms:

- job card: customer, vehicle, complaint, supervisor, dates
- job lines: labour, service, spare, combo items, quantities, employees
- invoice generation inputs: included lines, adjustments, notes
- payment form

Tables:

- job cards: number, customer, vehicle, status, advisor, opened at
- labour lines: employee, task, rate source, hours, status
- spare lines: item, quantity, warehouse, availability
- invoices: number, job card, total, balance, status

Actions:

- create job
- add customer and vehicle
- assign supervisor
- add labour, service, spare, combo items
- assign employees
- consume spare parts
- preview invoice
- generate invoice
- receive payment
- close job

Backend calculations needed:

- combo expansion
- labour assignment validation
- stock consumption effects
- invoice totals
- payment allocation

Frontend responsibilities:

- job workspace orchestration
- display stock and invoice preview

Permissions:

- `vehicle-service.view`
- `vehicle-service.create`
- `vehicle-service.invoice`
- `vehicle-service.receive-payment`
- `vehicle-service.close`

Missing backend endpoints:

- explicit invoice preview naming
- scheduler views
- combo preview
- payroll or incentive export if needed

### 15.16 Vehicle Rental

Screens:

- Rental Dashboard
- Vehicles Availability
- Rental Agreements
- Agreement Create or Edit
- Running Charts
- Running Chart Details
- Rental Invoice Preview
- Rental Invoices
- Replacement Vehicles
- Breakdowns
- Provider Payables
- Rental Payments
- Rental Settings

Frontend routes:

- `/app/vehicle-rental/dashboard`
- `/app/vehicle-rental/availability`
- `/app/vehicle-rental/agreements`
- `/app/vehicle-rental/agreements/new`
- `/app/vehicle-rental/agreements/:id`
- `/app/vehicle-rental/running-charts`
- `/app/vehicle-rental/running-charts/:id`
- `/app/vehicle-rental/invoices`
- `/app/vehicle-rental/provider-payables`
- `/app/vehicle-rental/payments`
- `/app/vehicle-rental/settings`

Required APIs:

- agreement CRUD
- running chart CRUD
- sync lines, rates, rules, charges
- vehicle availability
- billing preview
- provider payable actions
- replacement and breakdown actions
- workflow, status history, integration actions
- payment actions
- settings endpoints

Forms:

- agreement: customer, vehicle, rental type, period, rates, driver options, deposit
- running chart: trip dates, km, hours, route, overtime, night or weekend flags, expenses
- provider payable: provider, linked agreement or chart, adjustment notes
- payment form

Tables:

- availability: vehicle, status, available from, current assignment
- agreements: number, customer, vehicle, period, status, billing status
- running charts: number, agreement, date range, km, bill status
- provider payables: number, provider, source, amount, status

Actions:

- create agreement
- check vehicle availability
- create or edit running chart
- preview rental billing
- confirm invoice
- manage replacement vehicle
- log breakdown
- create provider payable
- receive payment
- close agreement

Backend calculations needed:

- availability
- km, hour, day, month calculations
- overtime and special-rate rules
- running chart final billing
- provider payable generation

Frontend responsibilities:

- agreement and running chart data entry
- present billing preview and workflow status

Permissions:

- `vehicle-rental.view`
- `vehicle-rental.create`
- `vehicle-rental.bill`
- `vehicle-rental.receive-payment`
- `vehicle-rental.close`

Missing backend endpoints:

- quotation
- calendar view endpoint
- damage claim flow
- refund endpoints
- explicit running chart preview naming

### 15.17 Voucher

Screens:

- Voucher Dashboard
- Voucher Types
- Vouchers
- Voucher Create or Edit
- Voucher Details
- Voucher Lines
- Voucher Approvals
- Voucher Allocations
- Voucher Settings

Frontend routes:

- `/app/voucher/dashboard`
- `/app/voucher/types`
- `/app/voucher/vouchers`
- `/app/voucher/vouchers/new`
- `/app/voucher/vouchers/:id`
- `/app/voucher/allocations`
- `/app/voucher/settings`

Required APIs:

- voucher type CRUD
- voucher CRUD
- voucher line CRUD
- posting preview, approve, post, reverse
- allocations
- settings endpoints

Forms:

- voucher: type, date, reference, party, memo
- lines: account, debit or credit, cost center, notes
- allocation: source, target, amount

Tables:

- vouchers: number, type, date, status, debit total, credit total
- voucher lines: account, debit, credit, cost center
- approvals: voucher, step, approver, status, acted at

Actions:

- create voucher
- preview posting
- approve
- post
- reverse
- allocate linked balances

Backend calculations needed:

- debit and credit validation
- posting preview
- payment integration effects

Frontend responsibilities:

- voucher entry and approval UI

Permissions:

- `voucher.view`
- `voucher.create`
- `voucher.approve`
- `voucher.post`
- `voucher.reverse`

Missing backend endpoints:

- recurring voucher templates
- richer allocation summaries

## 16. Dashboard Widget Plan

Global dashboard widgets:

- overdue receivables
- overdue payables
- low stock items
- pending approvals
- recent payments
- recent stock movements
- revenue and expense trend
- workflow inbox

Module dashboard widgets:

- Purchase: open PO, pending GRN, supplier invoices due, return value
- Sales: open orders, pending deliveries, overdue invoices, refund count
- Inventory: low stock, blocked stock, pending counts, transfer backlog
- Payment: unallocated payments, refunds pending, advances available
- Vehicle Service: open jobs, waiting parts, unpaid invoices, closing backlog
- Vehicle Rental: vehicle utilization, active agreements, running charts pending billing, provider payables due
- Voucher: vouchers awaiting approval, unposted vouchers, reversals this period

## 17. Form Field Planning Rules

- Every transactional line form must support item, description, quantity, UOM, requested rate, tax group, discount input, warehouse or location where applicable, notes, and backend preview output.
- Every master data form must include status, tenant-scoped identifiers, optional metadata, attachment support if relevant, and audit visibility.
- Party forms must separate profile, contacts, addresses, tax, finance defaults, and optional user access into independent sections.
- Vehicle service and rental forms must separate operational inputs from financial preview outputs.

## 18. Table Column Planning Rules

- Every list should include primary identifier, party or entity, status, date, backend total or balance, and updated at.
- Every transaction table should expose workflow state, financial state, and integration state where relevant.
- Cost-sensitive columns should be permission-gated.
- Tables should support saved filters, export, column visibility, and row-level quick actions.

## 19. Action And Button Planning Rules

- Primary page actions: create, save draft, preview, submit, approve, post, reverse, refund, close
- Secondary actions: duplicate, print, download, attach, comment, audit, export
- Row actions: view, edit, workflow action, preview, delete only where soft-delete or draft-only is allowed
- Critical actions must open `ConfirmDialog` with backend impact summary if preview is available

## 20. Implementation Phases

### Phase 1: Platform Shell

- app layout
- auth and session handling
- sidebar and top nav
- route system
- API client
- error handling
- permission system
- reusable components foundation

### Phase 2: Master Data Modules

- Supplier
- Customer
- HR
- Item
- UOM
- Pricing

### Phase 3: Core Operational Modules

- Document
- Finance
- Inventory
- Payment

### Phase 4: Business Modules

- Purchase
- Sales
- Vehicle Service
- Vehicle Rental
- Voucher

### Phase 5: Analytics And Hardening

- dashboards
- reports
- polish
- accessibility
- performance tuning
- end-to-end testing

## 21. Testing Checklist

- auth login, logout, session expiry, tenant switching
- route guard coverage per permission
- module navigation visibility
- list page loading, filter, sort, pagination, export
- create and edit forms with client-side and backend validation states
- preview endpoint usage before critical submit
- detail page tabs, audit, attachments, comments
- workflow action visibility and confirmation behavior
- payment allocation and stock availability preview rendering
- error handling for 401, 403, 404, 409, 422, 500
- optimistic UI limited to non-critical interactions
- tenant isolation in selectors and queries
- accessibility of tables, dialogs, forms, and keyboard navigation
- responsive layouts for desktop and tablet operational workflows

## 22. Remaining Backend Risks Affecting Frontend Planning

- Document render and download contract should be made explicit before document preview UI is finalized.
- Several modules still return generic `DataRecord` payloads. Frontend API typing should use adapters until response contracts are more explicit.
- Finance reporting endpoints are not yet rich enough for a full accounting dashboard.
- HR payroll and leave workflows need deeper preview and approval surfaces before building advanced HR operations UI.
- Vehicle Service and Vehicle Rental would benefit from more explicitly named preview endpoints to simplify frontend orchestration.

## 23. Recommended Next Deliverables After Blueprint Approval

- frontend route manifest file
- permission code registry
- shared API type contracts
- reusable component inventory with props
- wireframe set for each module
- implementation backlog per phase
