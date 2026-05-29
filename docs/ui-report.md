# Domain-Driven Frontend Reimplementation Knowledgebase and Agent Prompt

## What the references should mean

The screenshots and `tmp/resources` should be treated as **visual and implementation references**, not as the final product definition. The real source of truth for how this application should behave is the business context, the architecture rules, the frontend blueprint, and the frontend/backend audit/checklist. In other words, the images should influence the **visual language** of the UI, but they must not dictate the workflow, field semantics, module boundaries, or record lifecycle if those conflict with the domain model. fileciteturn0file0 fileciteturn0file1 fileciteturn0file2

That distinction matters because this platform is not a one-off workshop app or a fixed ERP clone. It is meant to be a modular, multi-tenant, plug-and-play enterprise platform where each business capability owns its workflow while shared platform modules remain reusable and loosely coupled. A front end that blindly mirrors two job-card screenshots would violate that intent almost immediately. fileciteturn0file1

The correct reading of the references is therefore this: use the screenshots for the **feel** of the product—clean sidebar, calm topbar, white cards, rounded controls, spacious forms, step indicators, and modern SaaS density—but redesign the final UI so it fits the real module behavior described in the business and architecture documents. fileciteturn0file0 fileciteturn0file2

## Business truths that must override the screenshots

Invoices are a shared capability, but they are not one generic user journey. The business context explicitly states that invoices can originate from Sales, Purchase, Vehicle Service, Vehicle Rental, and future modules, and that each source module has different line types, charge logic, references, and traceability needs. The same pattern applies to payments: the payment framework is shared, but customer receipts, supplier payments, service payments, rental payments, advances, refunds, and provider settlements are operationally different. That means the UI should feel **module-local first** and **shared-core second**. fileciteturn0file0 fileciteturn0file1

The frontend contract is also explicit: the frontend is not allowed to calculate authoritative totals, taxes, discounts, balances, stock effects, finance postings, UOM conversions, workflow states, returns, or rental/service billing. It may collect inputs and display backend previews, but backend services remain authoritative. So if a screenshot shows fields that look editable—such as payment status, final totals, or workflow status—they cannot simply be copied into the new UI as if they were normal form fields. They must either become backend-driven displays, backend-approved inputs, or be moved to a later workflow stage. fileciteturn0file2 fileciteturn0file4

This is especially important for the Vehicle Service screenshots. The images are useful for understanding layout rhythm, but several visible elements should be reinterpreted before implementation. A “Manual Job Card #” field should only exist if the business really allows manual override; otherwise the correct UX is a backend sequence preview or a readonly generated value. A “Payment Status” dropdown on the initial intake screen is visually plausible but domain-wise it usually belongs downstream, after invoice issuance or receipt activity. The product search and order table are useful patterns, but the real domain requires separation between stock items, non-inventory items, external services, and customer-supplied items so stock behavior remains correct. fileciteturn0file0 fileciteturn0file2 fileciteturn0file3

## Recommended navigation and workspace architecture

Your instinct about keeping module-specific invoices and payments inside each business module is strong. From a UX perspective, Purchase users should naturally find supplier invoices and supplier payments inside Purchase; Sales users should find customer invoices and customer payments inside Sales; Vehicle Service users should find service invoices and service payments inside Vehicle Service; Vehicle Rental users should find rental invoices, rental payments, and provider payables inside Vehicle Rental. That matches the business workflows described in the requirements and reduces context switching for end users. It also preserves source traceability, which the invoice model explicitly requires. fileciteturn0file0 fileciteturn0file2

At the same time, the architecture documents are right that Invoice and Payment are shared platform capabilities. The best UI interpretation is therefore a **hybrid navigation model**. The daily operational workflow should live under the business modules, while the shared/core modules should remain available as cross-module administration and monitoring consoles for accountants, auditors, finance staff, and system administrators. That means a Purchase user should not need to leave Purchase to make a supplier payment, but a finance user can still use the generic Payments or Finance area to review all allocations, reconciliations, checks, or cash-register activity across modules. fileciteturn0file1 fileciteturn0file2

A strong left-navigation structure for this product would therefore use grouped, expandable module menus rather than trying to scale the small reference sidebar literally. A clean version of that would look like this:

```text
Dashboard

Master Data
  Customers
  Suppliers
  Employees
  Items
  UOM
  Pricing
  Vehicles

Operations
  Purchase
    Dashboard
    Orders
    GRNs
    Supplier Invoices
    Supplier Payments
    Advances
    Returns
    Refunds
  Sales
    Dashboard
    Orders
    Deliveries
    Customer Invoices
    Customer Payments
    Advances
    Returns
    Refunds
  Vehicle Service
    Dashboard
    Job Cards
    Service Invoices
    Service Payments
    Service History
  Vehicle Rental
    Dashboard
    Availability
    Agreements
    Running Charts
    Rental Invoices
    Rental Payments
    Provider Payables
  Vouchers

Core
  Inventory
  Finance
  Payments
  Documents

Administration
  Tenant
  Users & Permissions
  Organization Units
  Configuration
  Audit
```

This is a domain-first adaptation of the blueprint’s grouped navigation, not a contradiction of it. The screenshot sidebar remains useful as a style reference, but the final IA should scale to the real module surface. fileciteturn0file2

## Vehicle service interaction model

For Vehicle Service, the screenshot’s two-step flow is a useful hint, but it is too narrow for the actual domain. The business requirements describe a service job that can include customer/vehicle intake, service items, labour items, combo items, stock parts, non-inventory items, external services, customer-supplied items, employee assignment, labour incentive/sharing, service invoice generation, payments, and service history. The frontend blueprint also already expects multiple service-specific screens and routes such as job cards, items, labour, parts, external services, customer-supplied lines, invoice, and payments. fileciteturn0file0 fileciteturn0file2

Because of that, my recommended UI model is **three primary tabs for create/edit**, followed by separate downstream invoice/payment workspaces after the record exists.

The three primary tabs should be:

```text
Tab 1
Intake & Header

Tab 2
Job Lines

Tab 3
Labour & Assignment
```

That structure works better than copying the screenshot’s “New Job / Crew Members” literally. “Intake & Header” covers customer, vehicle, supervisor, job type, complaint, odometer, dates, notes, and intake metadata. “Job Lines” becomes the domain-correct place for all job content, with secondary segmented sections or subtabs inside it such as Service Items, Spare Parts/Stock Items, Non-Inventory Items, External Services, and Customer-Supplied Items. “Labour & Assignment” becomes the dedicated workspace for labour lines, technician allocation, combo expansion follow-up, and incentive/share distribution. This aligns far better with the actual service center behavior described in the requirements. fileciteturn0file0

I would **not** put invoice and payment inside those three initial tabs. A service invoice is a downstream artifact generated from the job card, and payments are downstream financial activity. The audit and implementation checklist already reinforce that service invoices, payments, finance posting, and authoritative totals belong to backend-driven workflows. So the cleaner model is: create/save the job card first, then expose secondary detail tabs or sibling pages for Invoice Preview / Service Invoice, Payments, Attachments, Comments, Audit, and History. fileciteturn0file3 fileciteturn0file4

This also fixes one of the biggest hidden problems in the screenshot reference: it visually mixes intake, operational job content, crew allocation, and financial status much too early. The new UI should keep the same visual sophistication, but separate those responsibilities according to the real domain.

## Reimplementation rules for `tmp/resources` and `resources`

`tmp/resources` should be mined like a **component quarry**, not transplanted like a finished app. From a code perspective, it already contains useful layout, table, form, feedback, and feature-page ideas. Those can save time. But the old structure, assumptions, routing, and feature grouping should not be imported wholesale into the new `resources` architecture. The right move is to break out the reusable parts, redesign their API and responsibility, and re-place them inside the maintainable module-first structure you already want.

The safest extraction approach is this:

- Move old low-level UI primitives into `resources/ts/shared/components/ui`.
- Move table/filter/pagination patterns into `resources/ts/shared/components/data`.
- Move generic form scaffolding into `resources/ts/shared/components/forms`.
- Move layout ideas such as sidebar, topbar, breadcrumbs, global search, and page header into `resources/ts/layouts/components`.
- Move feature-specific editors and line-entry widgets into their owning module folders under `resources/ts/modules/.../components`.
- Rewrite route definitions, module page composition, state flow, and mock service layer according to the new architecture rather than preserving the old `tmp/resources/js/src/features/...` assumptions.

The rule is simple: **reuse patterns, not technical debt**.

It is also worth making one architectural distinction very explicit for the next agent. Shared components may be reused across modules, but shared screens should not become fake universal business screens. For example, a `LineItemsEditor`, `MoneyInput`, `TaxBreakdownPanel`, `DocumentPreview`, `PaymentAllocationTable`, or `StatusBadge` can be shared. But Purchase Invoice Create, Sales Invoice Create, Service Invoice Preview, and Rental Billing Preview should remain module-owned pages or module-owned wrappers around shared primitives, because the business rules and exposure fields differ by module. fileciteturn0file0 fileciteturn0file1 fileciteturn0file2

## Optimized end-to-end agent prompt

```text
You are re-implementing the frontend UI for my modular enterprise business management platform.

This is NOT a simple screenshot-clone task.

You must deeply understand the domain, module boundaries, workflow differences, and frontend architecture before designing or implementing anything.

READ FIRST — REQUIRED KNOWLEDGE BASE

Before coding, carefully study and treat these as source of truth in this exact order:

1. application_business_context_requirements.md
2. ARCHITECTURE.md
3. FRONTEND_IMPLEMENTATION_BLUEPRINT.md
4. MODULE_FRONTEND_BACKEND_AUDIT.md
5. MODULE_FRONTEND_BACKEND_IMPLEMENTATION_CHECKLIST.md
6. tmp/resources
7. tmp/Html - Body.png
8. tmp/Html - Body (1).png

INTERPRETATION RULES

Very important:

- The screenshots are ONLY for visual direction and UI feel.
- Do NOT copy the screenshots literally.
- Do NOT assume the screenshots represent the correct final workflow.
- Do NOT assume every visible field in the screenshots is domain-correct.
- The business/domain documents are more important than the screenshots.
- If the screenshots conflict with business requirements, architecture, or frontend/backend ownership rules, the documents win.

Also:

- Do NOT copy tmp/resources blindly.
- Do NOT preserve old messy structure.
- Do NOT migrate legacy routing or feature grouping as-is.
- Use tmp/resources only as a reusable component/pattern source.
- Break reusable parts into shared components.
- Re-design and re-implement them cleanly in the new architecture under resources.
- Reuse useful primitives, patterns, and good code only where appropriate.

TECH STACK

Use:

- React
- TypeScript
- Tailwind CSS
- Context API for now

Do NOT connect the real backend yet.

Implement the complete UI first using:

- typed mock data
- mock service functions
- API placeholder files
- clean future integration points

CORE PRODUCT UNDERSTANDING

This platform is a modular multi-tenant enterprise SaaS platform.

It is not a single fixed ERP flow.

Modules are business capabilities.

Shared/core modules provide reusable services.

Business modules own their workflows.

Main business modules:
- Purchase
- Sales
- VehicleService
- VehicleRental
- Voucher

Core/shared modules:
- Document
- Finance
- Inventory
- Payment
- Item
- UOM
- Pricing
- Supplier
- Customer
- HR
- Tenant
- Configuration

IMPORTANT DOMAIN RULES

1. UI must be domain-first, not screenshot-first.
2. Modules must feel independent and workflow-oriented.
3. Shared components are allowed.
4. Shared full business screens should not force unrelated modules into one rigid flow.
5. Frontend must not implement business-critical calculations.
6. Backend remains authoritative for totals, taxes, discounts, stock effects, balances, postings, workflow statuses, refunds, returns, rental billing, and service invoice totals.
7. For now, use mock preview data or mock-calculated display data only as placeholders.
8. Keep all future calculation and posting points clearly isolated for backend integration later.

FRONTEND OWNERSHIP RULE

Frontend should:
- collect input
- manage UX state
- render lists/forms/detail pages
- render previews
- show status/history/audit
- display mock totals for now where needed
- remain ready for backend preview endpoints later

Frontend must NOT become a shadow ERP engine.

UI DIRECTION RULE

Use the screenshots only to infer the visual language:

- modern SaaS / enterprise ERP feel
- clean sidebar
- compact topbar
- spacious work area
- white cards on light background
- stepper/tab patterns
- modern rounded inputs
- clean tables
- clear action hierarchy
- minimal clutter

Do NOT copy the screenshot workflows or field layout blindly.

If the domain requires a better layout, redesign it.

WORKFLOW-FIRST NAVIGATION RULE

Use workflow-oriented module navigation.

For daily users, invoices and payments must appear inside their business modules.

Recommended left navigation:

Dashboard

Master Data
- Customers
- Suppliers
- Employees
- Items
- UOM
- Pricing
- Vehicles

Operations
- Purchase
  - Dashboard
  - Orders
  - GRNs
  - Supplier Invoices
  - Supplier Payments
  - Advances
  - Returns
  - Refunds
- Sales
  - Dashboard
  - Orders
  - Deliveries
  - Customer Invoices
  - Customer Payments
  - Advances
  - Returns
  - Refunds
- Vehicle Service
  - Dashboard
  - Job Cards
  - Service Invoices
  - Service Payments
  - Service History
- Vehicle Rental
  - Dashboard
  - Availability
  - Agreements
  - Running Charts
  - Rental Invoices
  - Rental Payments
  - Provider Payables
- Vouchers

Core
- Inventory
- Finance
- Payments
- Documents

Administration
- Tenant
- Users & Permissions
- Organization Units
- Configuration
- Audit

Important:
- Module-local invoice/payment menus are the main workflow UX.
- Shared/core Payments and Documents may still exist as cross-module admin/accounting consoles.
- Do not force users to leave Purchase/Sales/VehicleService/VehicleRental just to continue their own workflow.

VEHICLE SERVICE UI DECISION

Do NOT copy the screenshot’s two-step flow literally.

Use a better domain-aligned create/edit workspace.

Recommended primary tabs for Vehicle Service Job Card:

Tab 1:
- Intake & Header

Tab 2:
- Job Lines

Tab 3:
- Labour & Assignment

Inside Intake & Header:
- customer
- vehicle
- supervisor/service advisor
- job type / service type
- complaint / notes / diagnosis placeholders
- odometer / mileage
- dates
- job metadata
- manual job card number only if business requires manual override
- otherwise prepare UI for backend sequence preview later

Inside Job Lines:
use grouped sections or nested secondary tabs for:
- Service Items
- Spare Parts / Stock Items
- Non-Inventory Items
- Customer-Supplied Items
- External Services
- Combo Items / Expanded components if needed

Important:
- Do not mix stock and non-stock lines ambiguously.
- Make stock-affecting lines visually distinct from non-stock lines.
- Keep invoiceable but non-stock lines clearly labeled.

Inside Labour & Assignment:
- labour items
- assign employees/technicians
- split/share/incentive inputs
- combo expansion follow-up if labour comes from bundled service items
- supervisor review cues

After the job card is saved, expose separate downstream pages/tabs/routes for:
- Invoice Preview / Service Invoice
- Service Payments
- Attachments
- Comments
- Audit / History

Do NOT make invoice and payment part of the first intake tabs unless the domain explicitly requires it.

Also:
- Do not treat “payment status” as a naive manual field just because it appears in the screenshot.
- If statuses exist in UI before backend integration, they must be clearly temporary/mock or backend-owned later.
- The same applies to final totals and financial values.

PURCHASE / SALES / RENTAL UX RULE

Purchase, Sales, Vehicle Service, and Vehicle Rental must each have their own:
- list screens
- create/edit flow
- detail workspace
- invoice screens
- payment screens

Do not create one generic invoice UI and force every module into it.

Instead:
- reuse shared table/form/preview/payment components
- wrap them in module-specific pages
- keep module-specific language and workflow visible

Examples:
- Purchase → Supplier Invoice, Supplier Payment
- Sales → Customer Invoice, Customer Payment
- Vehicle Service → Service Invoice, Service Payment
- Vehicle Rental → Rental Invoice, Rental Payment, Provider Payable

MODULE DETAIL WORKSPACES

Prefer this pattern:

List Page
- filters
- table
- quick actions
- create action

Create/Edit Page
- domain-specific tabs or steps
- sticky action/footer bar
- draft-friendly UX
- validation panels

Detail Page
- summary header
- status badges
- domain tabs
- invoice/payment/history/attachments/comments/audit sections
- route-based or tab-based sub-workspaces

TABS VS WIZARDS RULE

Do not use steppers everywhere just because the screenshot has a stepper.

Use:
- tabs for non-linear editing and revisiting sections
- steppers/wizards only when the workflow is truly sequential

Vehicle Service job cards should favor tabbed workspace behavior more than strict wizard behavior.

FILE STRUCTURE RULE

Implement everything under:

resources

Use a clean enterprise structure like:

resources/
  ts/
    app/
    routes/
    layouts/
    contexts/
    services/
    shared/
    modules/
    config/
    styles/
  css/
    app.css

Recommended deeper structure:

resources/ts/
  app/
    App.tsx
    bootstrap.tsx
    providers/
    guards/

  routes/
    index.tsx
    routePaths.ts
    lazyRoutes.tsx
    moduleRoutes/

  layouts/
    AppLayout.tsx
    AuthLayout.tsx
    BlankLayout.tsx
    components/

  contexts/
    AuthContext.tsx
    TenantContext.tsx
    PermissionContext.tsx
    ThemeContext.tsx
    SidebarContext.tsx
    AppSettingsContext.tsx

  services/
    api/
    mock/

  shared/
    components/
      ui/
      data/
      forms/
      business/
    hooks/
    types/
    utils/

  modules/
    dashboard/
    purchase/
    sales/
    vehicle-service/
    vehicle-rental/
    finance/
    inventory/
    payment/
    document/
    item/
    uom/
    pricing/
    supplier/
    customer/
    hr/
    voucher/
    settings/
    tenant/
    configuration/

  config/
  styles/

MODULE STRUCTURE RULE

Each module should contain:

module-name/
  pages/
  components/
  forms/
  tables/
  services/
  mock/
  contexts/
  hooks/
  types/
  routes.tsx
  index.ts

REUSE STRATEGY FOR tmp/resources

Audit tmp/resources deeply and map old assets into the new structure.

Allowed reuse examples:
- Button
- Card
- Input
- Select
- Textarea
- Checkbox
- Confirm modal/dialog
- Empty/error/loading states
- Data table
- Table toolbar/filter
- Status badge
- Breadcrumbs
- Page header
- Sidebar/topbar ideas
- search/autocomplete patterns
- generic form grid/card patterns

Not allowed:
- copy old folder structure into new resources
- keep old feature route assumptions
- keep old business assumptions if domain docs say otherwise
- copy-paste entire pages without redesign
- preserve old naming just because it exists

Transform old code into:
- shared primitives
- cleaner module components
- typed modern APIs
- consistent Tailwind patterns
- maintainable TypeScript-driven structure

DESIGN SYSTEM RULES

Create a consistent design system inspired by the screenshots but not limited to them.

Need:
- sidebar styles
- topbar styles
- typography scale
- spacing scale
- card styles
- form field styles
- button hierarchy
- tabs/stepper styles
- table styles
- status colors
- empty/loading/error states
- page header patterns

Visual target:
- clean
- professional
- operational
- enterprise
- minimal clutter
- workshop/fleet/business friendly
- not overly flashy

DO NOT over-style.

ROUTES TO IMPLEMENT

Implement complete UI routes for at least:

Dashboard
- /dashboard

Master Data
- /customers
- /customers/new
- /customers/:id
- /customers/:id/edit
- /suppliers
- /suppliers/new
- /suppliers/:id
- /suppliers/:id/edit
- /hr/employees
- /hr/employees/new
- /hr/employees/:id
- /items
- /items/new
- /items/:id
- /uom/units
- /uom/conversions
- /pricing/price-lists
- /pricing/rules
- /vehicles

Purchase
- /purchase
- /purchase/orders
- /purchase/orders/new
- /purchase/orders/:id
- /purchase/grns
- /purchase/grns/new
- /purchase/grns/:id
- /purchase/invoices
- /purchase/invoices/new
- /purchase/invoices/:id
- /purchase/payments
- /purchase/advances
- /purchase/returns
- /purchase/refunds

Sales
- /sales
- /sales/orders
- /sales/orders/new
- /sales/orders/:id
- /sales/deliveries
- /sales/invoices
- /sales/invoices/new
- /sales/invoices/:id
- /sales/payments
- /sales/advances
- /sales/returns
- /sales/refunds

Vehicle Service
- /vehicle-service
- /vehicle-service/job-cards
- /vehicle-service/job-cards/new
- /vehicle-service/job-cards/:id
- /vehicle-service/job-cards/:id/edit
- /vehicle-service/invoices
- /vehicle-service/invoices/:id
- /vehicle-service/payments
- /vehicle-service/history

Vehicle Rental
- /vehicle-rental
- /vehicle-rental/availability
- /vehicle-rental/agreements
- /vehicle-rental/agreements/new
- /vehicle-rental/agreements/:id
- /vehicle-rental/running-charts
- /vehicle-rental/running-charts/:id
- /vehicle-rental/invoices
- /vehicle-rental/invoices/:id
- /vehicle-rental/payments
- /vehicle-rental/provider-payables

Core
- /inventory
- /inventory/stock-levels
- /inventory/movements
- /inventory/reservations
- /inventory/transfers
- /finance
- /finance/accounts
- /finance/journal-entries
- /finance/tax
- /finance/banks
- /payments
- /documents

Other
- /vouchers
- /vouchers/new
- /vouchers/:id
- /settings
- /tenant
- /configuration
- /audit

IMPLEMENTATION SCOPE

This is not a skeleton-only task.

You must implement the frontend UI end to end.

That means:
- app shell
- routing
- layout
- sidebar
- module sub-navigation
- dashboards
- list pages
- create/edit pages
- detail pages
- action bars
- tables
- forms
- tabs
- placeholder previews
- invoice/payment UI pages
- history/attachment/comment panels where appropriate
- typed mock data
- mock services
- API placeholders

Do NOT stop at placeholder pages once the shell is ready.

Placeholders are acceptable only temporarily for minor low-priority pages, but the core modules must have real UI composition.

PRIORITY ORDER

Build in this order:

Phase A
- audit all references
- summarize domain and UI decisions
- identify what can be reused from tmp/resources

Phase B
- design system
- app shell
- sidebar
- topbar
- route structure
- shared components

Phase C
- master data modules
  - customer
  - supplier
  - hr
  - item
  - uom
  - pricing
  - vehicle registry if needed

Phase D
- core modules
  - inventory
  - finance
  - payment
  - document

Phase E
- business modules
  - purchase
  - sales
  - vehicle service
  - vehicle rental
  - voucher

Phase F
- QA polish
- consistency pass
- responsive pass
- cleanup/refactor

BACKEND INTEGRATION RULE

Do NOT connect the real backend yet.

Instead:
- create service placeholder files
- create typed mock responses
- keep future integration points isolated
- do not mix business logic inside UI components

QUALITY BAR

The code must be:
- readable
- maintainable
- modular
- strongly typed
- route-structured
- scalable
- enterprise-level
- cleanly named
- consistent
- reusable
- code-splitting ready

Avoid:
- huge page components
- repeated markup
- blind copy-paste
- mixed concerns
- hard-coded business logic
- hard-coded calculations
- old tmp/resources structure inheritance
- screenshot cloning
- generic one-size-fits-all business screens

VERY IMPORTANT DECISION RULE

Think deeply before implementing.

Do not design blindly.

Do not code only from visual references.

Do not assume that what looks nice is automatically correct for the business.

Use the domain knowledge first.
Use architecture rules second.
Use frontend contract third.
Use screenshots fourth.
Use tmp/resources as a reusable source only after all of the above are understood.

If there is any conflict:
domain and architecture win.

DELIVERABLE EXPECTATION

Deliver a complete, domain-aligned, visually polished frontend reimplementation under resources that:

- respects the modular business architecture
- uses React + TypeScript + Tailwind CSS
- uses Context API for now
- is maintainable and scalable
- does not blindly copy tmp/resources
- does not blindly clone the screenshots
- uses the screenshots only as style inspiration
- uses tmp/resources only as reusable source material
- gives each business module its own correct workflow-oriented UI
- keeps invoices and payments module-local where appropriate
- keeps shared/core admin areas where appropriate
- is ready for later backend preview/API integration

BEFORE YOU START CODING

First, provide a short but concrete implementation plan containing:
- extracted source-of-truth decisions from the documents
- final left-nav structure
- final vehicle service workspace structure
- reuse map from tmp/resources → new resources paths
- phased implementation sequence

Then implement.
```

This prompt is optimized around the actual domain documents, the frontend contract, the architecture rules, and your explicit corrections about how the screenshots and `tmp/resources` should be used. The key change is that it tells the next agent to treat the screenshots as **style references only**, to treat `tmp/resources` as **reusable source material only**, and to build the entire UI around the real business workflows of Purchase, Sales, Vehicle Service, and Vehicle Rental rather than around the current mock screenshot flow. fileciteturn0file0 fileciteturn0file1 fileciteturn0file2 fileciteturn0file3 fileciteturn0file4