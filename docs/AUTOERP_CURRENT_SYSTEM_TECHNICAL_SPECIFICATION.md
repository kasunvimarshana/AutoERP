# AutoERP Current System and Technical Specification

**Snapshot date:** 2026-09-18  
**Repository branch:** `dashboard-phase2`  
**Git commit:** `eea8064c2ae0`  
**Status:** Current implementation reference, not a legal, tax, security-certification, or production-readiness certificate

## 1. Purpose and authority

This document is the durable reference for the AutoERP implementation that exists in the repository at the snapshot above. It explains the implemented product scope, frameworks, architecture, design patterns, data-integrity model, security controls, important workflows, operational requirements, and known boundaries.

The source code remains authoritative. When this document and code disagree, verify the current branch and update this document. Detailed chronological history remains in the append-only [`docs/changes`](changes/) records. The architecture/release index is [`docs/README.md`](README.md), and the broader AI engineering guide is [`docs/AI_SYSTEM_CONTEXT.md`](AI_SYSTEM_CONTEXT.md).

“Finalized” in this document means implemented in the current code snapshot. It does not mean that every possible ERP domain is complete, that every deployment environment is correctly configured, or that production acceptance testing has been completed.

## 2. Product summary

AutoERP is a multi-tenant, vehicle-oriented ERP implemented as a modular monolith. It combines platform and tenant administration, access control, master data, procurement, stock and valuation, billing, payments, accounting, tax, vehicle service, vehicle rental, reporting, vouchers, audit, and protected document handling.

The system has two operating planes:

- **Platform control plane:** platform operators manage tenants, plans, subscriptions, platform configuration, security sessions, audit, and health.
- **Tenant business plane:** tenant users work inside an explicitly resolved tenant and, where required, an organization unit.

The system is designed around authoritative module ownership. For example, Inventory owns stock, Invoice owns invoices, Payment owns money-movement documents, and Finance owns journals and ledgers. Coordinating modules call those owners through contracts rather than duplicating their rules.

## 3. Implemented feature catalogue

### 3.1 Platform, tenancy, and administration

| Area | Current implemented capability | Primary source |
|---|---|---|
| Authentication | Separate tenant-user and platform-operator login, refresh, logout, session listing/revocation, tenant OAuth authorization-code exchange, organization-unit switching | [`Auth`](../app/Modules/Auth/), [`Auth routes`](../app/Modules/Auth/Routes/api.php) |
| Tenant management | Tenant plans/revisions, subscriptions/events, lifecycle, onboarding, domains and verification, tenant documents, storage accounting/cleanup, infrastructure targeting, health operations | [`Tenant`](../app/Modules/Tenant/) |
| Organization units | Hierarchy, legal profile, user access, current organization-unit context, lifecycle controls | [`OrganizationUnit`](../app/Modules/OrganizationUnit/) |
| Users and access | Tenant users, platform operators, roles, permissions, direct permission assignments, organization access, devices and user documents | [`User`](../app/Modules/User/) |
| Configuration | Global, tenant, and organization-unit configuration, definitions, resolved values, immutable revisions, history, rollback, import preview/apply and export | [`Configuration`](../app/Modules/Configuration/) |
| Reference and numbering | Controlled reference values and persistent document-number sequences | [`ReferenceData`](../app/Modules/ReferenceData/), [`Sequence`](../app/Modules/Sequence/) |
| Audit | Sanitized tenant and platform audit events with permission-controlled list/detail views | [`Audit`](../app/Modules/Audit/) |
| Protected objects | Tenant-aware private object storage/access boundary | [`PrivateObject`](../app/Modules/PrivateObject/) |

### 3.2 Master data

| Area | Current implemented capability | Primary source |
|---|---|---|
| Customers | Profiles, contacts, addresses, bank accounts, categories, credit profiles, documents, status history, tax integration, vehicle ownership, WhatsApp number verification | [`Customer`](../app/Modules/Customer/) |
| Suppliers | Profiles, contacts, addresses, bank accounts, categories, credit profiles, item mappings, documents, status history, tax integration, vehicle ownership, WhatsApp number verification | [`Supplier`](../app/Modules/Supplier/) |
| HR | Employees, departments, designations, employment types, contacts, addresses, documents, skills, certifications, licences, rates, availability and status history | [`Hr`](../app/Modules/Hr/) |
| Items | Stock, consumable, non-stock, service, labour, combo/package and controlled other item types; brands, categories, variants, codes, UOMs, bundles, usage rules, temporal prices and reorder levels | [`Item`](../app/Modules/Item/) |
| Units of measure | UOM masters, categories/types, conversions, conversion validation and usage-safety checks | [`UOM`](../app/Modules/UOM/) |
| Warehouses | Warehouses, locations, active/default source resolution and lifecycle controls | [`Warehouse`](../app/Modules/Warehouse/) |
| Vehicles | Vehicle master, make/model/type/category, ownership, attributes, documents, availability and status history | [`Vehicle`](../app/Modules/Vehicle/) |

### 3.3 Procurement, stock, billing, and payments

| Area | Current implemented capability | Primary source |
|---|---|---|
| Purchase | Purchase orders and approval, adjustments, goods receipts, batch allocation, supplier invoice linkage, payments, purchase returns, manual supplier returns, debit notes, document capabilities and Fast Purchase orchestration | [`Purchase`](../app/Modules/Purchase/) |
| Inventory | On-hand/reserved/allocated/available balances; receipts, issues, reservations, allocations, adjustments, transfers, counts, opening imports, batches/lots/serials, valuation layers, valuation consumption, cost adjustments and reversals | [`Inventory`](../app/Modules/Inventory/) |
| Inventory availability | Filterable stock-health overview with item/warehouse/stock-level filters, summary counts, human-readable locations, reorder status, on-hand, reserved and available quantities | [`Inventory frontend`](../resources/js/modules/inventory/), [change record](changes/2026-09-16-inventory-availability-overview.md) |
| Invoice | Purchase, sales, service, rental, manual, credit and debit invoice records; calculation, source allocation, balances, immutable snapshots, issue/post/settle/reverse/cancel/void lifecycles and print/share documents | [`Invoice`](../app/Modules/Invoice/) |
| Payment | Supplier payments, customer/service/rental receipts, advances, refunds, manual payments, methods, lines, allocations, unapplied balances, cheque templates/printing, lifecycle events, posting and reversal | [`Payment`](../app/Modules/Payment/) |
| Voucher | Read/presentation views over authoritative transaction sources without duplicating transaction ownership | [`Voucher`](../app/Modules/Voucher/) |

Important current document behavior:

- Service invoices and Purchase-owned supplier invoices use a focused compact A5 portrait presentation.
- Their item table shows Item Name, Quantity, Unit Price and Amount, while removing duplicated reference/description presentation.
- The summary shows Total Amount including VAT, Credit and Balance due; Paid appears when applicable.
- Mode of Payment is resolved from immutable active payment-allocation snapshots, with invoice snapshot/Credit fallback.
- Service Invoice Original/Duplicate labels are payment-gated: pre-payment prints are unlabeled; the first post-payment print is Original and later prints are Duplicate.
- An approved purchase order cannot offer Create Supplier Invoice until some quantity has actually been received.

See [focused invoice print record](changes/2026-09-18-service-and-supplier-invoice-focused-portrait-print.md), [copy-label record](changes/2026-09-16-payment-gated-service-invoice-copy-labels.md), and [supplier-invoice capability record](changes/2026-09-16-hide-supplier-invoice-action-before-po-receipt.md).

### 3.4 Accounting, tax, and reporting

| Area | Current implemented capability | Primary source |
|---|---|---|
| Finance | Chart of accounts, roles, dimensions, effective posting profiles, balanced journals, ledger entries, balances, accounting periods, reversals, budgets, bank reconciliation, trial balance, general ledger, profit and loss, balance sheet, cash flow, AR/AP aging and tax liability views | [`Finance`](../app/Modules/Finance/) |
| Tax | Tax/rate masters, groups, customer/supplier profiles, posting profiles, determination, calculation, immutable snapshots, transactions, returns and reports | [`Tax`](../app/Modules/Tax/) |
| Reporting | Report catalogue, permissions, templates, pagination/export, summary and detailed operational reports, purchase/GRN payables, technician work, employee commissions/incentives, vehicle service history, profitability and rental reports | [`Reporting`](../app/Modules/Reporting/) |
| Dashboard | Permission-aware Business Overview for revenue, receivables/payables, cash flow, service jobs, inventory value/health, action-required signals, ledger-backed profitability and top employee performance | [`Dashboard frontend`](../resources/js/modules/dashboard/), [Phase 1 record](changes/2026-09-14-business-overview-dashboard.md), [Phase 2 record](changes/2026-09-15-business-overview-dashboard-phase-2.md) |

Finance is the authoritative accounting source. Other modules provide semantic business facts; Finance resolves effective accounts and owns journals, ledger entries, periods, balances, posting profiles, and reversals. A payment settles a receivable/payable or cash position and does not create duplicate revenue or expense.

### 3.5 Vehicle operations

| Area | Current implemented capability | Primary source |
|---|---|---|
| Vehicle Service | Jobs, inspection, complaints/diagnosis/work, inventory/external/service/labour/combo lines, employee assignments, commission policies, discounts, documents, lifecycle history, automatic inventory integration, invoices, payments and cancellation/reversal | [`VehicleService`](../app/Modules/VehicleService/) |
| Vehicle Rental | Effective-dated rates, agreements, vehicle/driver assignments, replacements, custody events, running charts, calculations, invoices/payments and operational/financial reports | [`VehicleRental`](../app/Modules/VehicleRental/) |

Vehicle Service inventory behavior is intentionally low-click but transactionally strict:

1. Saving a company-stock job line creates an Inventory-owned reservation in the same transaction.
2. Updating replaces the reservation atomically; deletion or eligible cancellation releases it while keeping history.
3. Starting a job releases each reservation and posts the exact stock issue and related finance effect atomically.
4. Insufficient available stock, missing batch data, or an ambiguous warehouse/location blocks the action instead of guessing.
5. Customer-supplied items do not reserve/issue company stock.

See [automatic stock reservation and issue](changes/2026-09-12-vehicle-service-automatic-stock-reservation-and-issue.md).

### 3.6 Document sharing and WhatsApp verification

The current sharing solution is a free user-assisted WhatsApp handoff, not a paid WhatsApp API integration:

- Service Invoice and Purchase Order detail pages can prepare a message and a `wa.me` destination.
- AutoERP does not claim automated send, delivery, read receipts, or recipient acceptance.
- Public financial-document URLs are signed, expire, and are rechecked against tenant/document lifecycle rules.
- The default link lifetime is configuration-controlled; existing authenticated downloads remain independent.
- The browser opens a pending new tab during the user click. If the number is unverified, confirmation occurs in that pending tab; confirm navigates it to WhatsApp, cancel/error closes it, and popup blocking falls back safely to the current tab.

Manual WhatsApp number verification stores hashed, short-lived challenge codes with attempt limits and route rate limiting. Verification records retain the verified-number snapshot and verifier; a changed phone number becomes unverified until checked again. This is employee-assisted evidence, not carrier/API-level verification.

Primary references: [`whatsAppNavigation.ts`](../resources/js/shared/utils/whatsAppNavigation.ts), [`documentWhatsAppShareNavigation.test.ts`](../resources/js/shared/utils/documentWhatsAppShareNavigation.test.ts), [new-tab confirmation record](changes/2026-09-18-document-whatsapp-share-new-tab-confirmation.md), Customer/Supplier route and service code.

## 4. Technical stack

Exact installed versions below come from the lockfiles/current installed dependency tree at the snapshot date. Manifest constraints remain in [`composer.json`](../composer.json) and [`package.json`](../package.json).

### 4.1 Backend

| Component | Version / role |
|---|---|
| PHP | 8.2.12 in the inspected workspace; project requires PHP 8.2+ |
| Laravel Framework | 12.62.0 |
| Laravel Passport | 13.7.5; OAuth/access-token infrastructure is installed, while AutoERP provides its own explicit tenant/platform token services and guards |
| Laravel Reverb | 1.10.2 realtime infrastructure installed |
| BCMath | Required for exact decimal arithmetic |
| DOMPDF | 3.1.5 document/PDF rendering |
| Spatie Laravel PDF | 2.12.0 document/PDF rendering |
| L5 Swagger | 11.1.0 API documentation support |
| PHPUnit | 11.5.55 |

Composer namespaces:

- `App\\` → `app/`
- `Modules\\` → `app/Modules/`
- `Tests\\` → `tests/`

### 4.2 Frontend

| Component | Installed version / role |
|---|---|
| React / React DOM | 19.2.7 |
| TypeScript | 6.0.3, strict no-emit verification |
| React Router DOM | 7.18.1 |
| Axios | 1.18.1 API transport |
| Zustand | 5.0.14 focused client state |
| Tailwind CSS | 4.3.2 |
| Vite | 7.3.6 with Laravel integration 2.1.0 |
| Vitest | 4.1.9 with jsdom and Testing Library |
| React Toastify | 11.1.0 feedback/notifications |

The SPA entry is [`resources/js/app.tsx`](../resources/js/app.tsx); route composition is in [`resources/js/app/router.tsx`](../resources/js/app/router.tsx). Vite builds the React and CSS entry points and produces the deployable frontend bundle.

### 4.3 Persistence and runtime

- Laravel database connections support SQLite, MySQL/MariaDB, PostgreSQL, and SQL Server. Migrations use portable Laravel schema APIs.
- The inspected local environment currently uses MySQL, database-backed cache and queue, file sessions, log broadcasting, and SMTP mail.
- Financial/quantity arithmetic uses DECIMAL-compatible strings and [`DecimalMath`](../app/Modules/Core/Services/DecimalMath.php), backed by BCMath rather than binary floating point.
- Queue and scheduler processes are operational dependencies; configuring the driver does not prove a worker or scheduler is alive.

## 5. Architecture

### 5.1 Architecture style: modular monolith

AutoERP is one deployable Laravel application and one React SPA, divided into cohesive backend and frontend feature modules. Backend modules are explicitly registered in [`bootstrap/providers.php`](../bootstrap/providers.php). The snapshot registers 28 modules.

Typical backend module structure:

```text
app/Modules/<Module>/
  Constants/            shared fixed identifiers
  Contracts/            public ports/interfaces
  Database/Migrations/  module-owned schema
  DTOs/ and Data/        typed application inputs/results
  Enums/                controlled domain states
  Http/Controllers/     HTTP orchestration
  Http/Requests/        validation and request mapping
  Http/Resources/       stable API presentation
  Models/               Eloquent persistence entities
  Providers/            bindings, routes, configuration
  Repositories/         persistence adapters where useful
  Services/             application/domain behavior
  Tests/                module-level tests
```

Frontend feature code belongs in `resources/js/modules/<feature>`; app bootstrapping, route guards and layouts live in `resources/js/app`; genuinely reusable UI/API utilities live in `resources/js/shared`.

### 5.2 Request path and separation of concerns

```text
React page/component
  → typed feature API client
  → Laravel route and ordered middleware
  → FormRequest validation/normalization
  → thin controller
  → application/domain service
  → owning models and cross-module contracts
  → API Resource / stable response
```

The backend is authoritative for permissions, validation, business rules, calculations, lifecycles, transactions, tenancy, and data integrity. Frontend validation and action guards improve UX but never replace backend enforcement.

### 5.3 Module ownership boundaries

- **Core** owns execution context, base models, standard errors, correlation IDs, decimal math, and shared cross-cutting primitives.
- **Inventory** owns quantities, reservations, allocations, movements, tracking and valuation.
- **Invoice** owns commercial invoice documents, lines, balances, snapshots and lifecycle.
- **Payment** owns payment documents, instruments, methods, allocations, refunds and reversals.
- **Finance** owns accounts, posting configuration, journals, ledgers, periods, balances and statements.
- **Tax** owns tax determination/calculation facts and snapshots.
- **Purchase**, **VehicleService**, and **VehicleRental** own their workflows but delegate stock, invoice, payment, finance and tax effects to the authoritative modules.
- **Reporting** reads authoritative data and reusable reporting contracts; it does not own transaction rules.

Cross-module dependencies use public contracts and provider bindings. A current example is [`InvoicePaymentMethodProviderInterface`](../app/Modules/Invoice/Contracts/InvoicePaymentMethodProviderInterface.php): Invoice defines what its print view needs, while Payment supplies the implementation from immutable payment snapshots.

## 6. Design patterns actually used

| Pattern | How AutoERP uses it | Evidence/examples |
|---|---|---|
| Modular monolith / bounded modules | One deployable application with explicit business owners and module-local routes, migrations, services and tests | [`app/Modules`](../app/Modules/), [`bootstrap/providers.php`](../bootstrap/providers.php) |
| Layered application architecture | Requests flow through middleware, Form Requests, controllers, services, models/contracts and Resources | [`bootstrap/app.php`](../bootstrap/app.php), module `Http` and `Services` folders |
| Dependency inversion / ports and adapters | Owning/consuming modules depend on interfaces; service providers bind concrete adapters | [`Core Contracts`](../app/Modules/Core/Contracts/), [`Finance Contracts`](../app/Modules/Finance/Contracts/), [`Invoice Contracts`](../app/Modules/Invoice/Contracts/) |
| Service layer | Multi-step business operations and transaction boundaries live in services, not controllers | Module `Services` folders |
| Repository pattern, selectively | Repository interfaces/adapters are used where persistence substitution or query ownership helps; not forced on every model | Tenant, Audit and Configuration repositories |
| DTO / command-data pattern | Typed inputs/results cross controller/service and module boundaries | Module `DTOs` and `Data` folders |
| Strategy pattern | Inventory allocation/valuation and similar variable domain algorithms use explicit interfaces | [`AllocationStrategyInterface`](../app/Modules/Inventory/Contracts/AllocationStrategyInterface.php), [`ValuationMethodInterface`](../app/Modules/Inventory/Contracts/ValuationMethodInterface.php) |
| Registry/catalogue pattern | Permissions, configuration definitions, reports and source integrations register controlled capabilities | Permission/configuration/report registries and providers |
| State machine / guarded lifecycle | Enums plus services validate allowed document/status transitions and reject invalid transitions | Invoice, Payment, Purchase, Vehicle Service and Rental constants/services |
| Immutable snapshot | Printed/commercial facts, tax facts, payment-method names and related context are preserved at transaction time | Invoice, Tax and Payment snapshot fields/services |
| Double-entry ledger | Finance requires balanced journals and preserves ledger lineage | [`Finance`](../app/Modules/Finance/) |
| Reversal/compensating record | Posted history is corrected with linked opposite records rather than destructive edits | Finance, Invoice, Payment, Inventory, Purchase services |
| Optimistic concurrency | `row_version` / `expected_version` rejects stale writes | Requests and lifecycle services across modules |
| Pessimistic concurrency | `lockForUpdate()` protects shared stock, balances, token families and lifecycle aggregates | Inventory, Payment, Invoice, Auth and workflow services |
| Idempotent command | Operation/reference/payload hashes prevent duplicate multi-document execution and reject key reuse with a different payload | [`IdempotencyService`](../app/Modules/Idempotency/Services/IdempotencyService.php) |
| Outbox | Tenant integration/lifecycle events are stored for reliable publication/retry | [`Tenant event outbox`](../app/Modules/Tenant/Services/Events/TenantEventOutboxService.php) |
| Adapter/presentation layer | API Resources and frontend mappers expose structured, human-readable objects instead of database shapes | Module `Http/Resources`, frontend API/types |

These names describe existing code structure; AutoERP is not presented as microservices, event sourcing, or full CQRS.

## 7. Data and consistency model

### 7.1 Multi-tenancy and organization units

AutoERP uses shared-schema multi-tenancy. Tenant-owned rows include `tenant_id`; many operating records also include `organization_unit_id`.

[`TenantOwnedModel`](../app/Modules/Core/Models/TenantOwnedModel.php) combines:

- a global tenant query scope;
- fail-closed reads outside an authorized tenant execution context;
- required matching tenant ownership on create;
- immutable `tenant_id` after create;
- an explicit control-plane path for narrow cross-tenant platform work.

[`TenantScopedRequest`](../app/Modules/Core/Http/Requests/TenantScopedRequest.php) replaces client-supplied tenant/organization fields with values established by trusted request context. Relationship validation is tenant-scoped.

### 7.2 Transaction and concurrency rules

For shared financial, stock, authentication, and lifecycle state, services combine:

- database transactions;
- a consistent row-locking order;
- optimistic version checks;
- unique source identities and database constraints;
- idempotency for replay-sensitive orchestration;
- complete rollback on any dependent failure.

Every write should be considered concurrent. A UI having just read a row is not proof that the row is still current.

### 7.3 Exact numbers

Money, tax, rates, prices, quantities, costs and balances are represented as decimal strings. The authoritative backend uses BCMath at a normal six-decimal scale. JavaScript/PHP floating-point values must not become the source of truth for financial or stock calculations.

### 7.4 History and corrections

Posted transactions and original snapshots are not silently rewritten. Corrections use one or more of:

- a linked reversal/counter-movement;
- a superseding effective-dated revision;
- an append-only status/lifecycle event;
- a new adjustment or allocation;
- an immutable document/reference snapshot.

The schema catalogue is [`database/schema-docs/tables.md`](../database/schema-docs/tables.md).

## 8. API and frontend conventions

### 8.1 API

- Authenticated business APIs normally live under `/api/v1` and are registered by module providers.
- Success responses normally contain `data`; collections add pagination metadata. Successful deletes commonly return HTTP 204.
- [`ApiErrorResponseFactory`](../app/Modules/Core/Http/Responses/ApiErrorResponseFactory.php) normalizes authentication, authorization, validation, not-found, conflict, domain and unexpected errors.
- [`RequestCorrelationIdMiddleware`](../app/Modules/Core/Http/Middleware/RequestCorrelationIdMiddleware.php) assigns/propagates correlation IDs.
- Unexpected exceptions are logged with context, while API clients receive a generic server error instead of stack traces or secrets.
- Pagination is bounded; tenant-scoped requests default to 25 and cap `per_page` at 100.
- UI/API relationships use structured human-readable objects and controlled lookup inputs, not typed raw foreign keys.

### 8.2 Frontend

The route tree separates authentication, platform administration and tenant workspaces. Relevant guards include `ProtectedRoute`, `PlatformOperatorRoute`, `TenantRoute`, `TenantEntitlementRoute`, permission guards, and positive-integer route-parameter boundaries in [`router.tsx`](../resources/js/app/router.tsx).

UX principles implemented throughout the project:

- favor task speed and clear next actions;
- show essential fields in primary flows and move history/advanced details to focused views;
- present relationship names/codes rather than raw IDs;
- display field/action errors clearly;
- refresh from authoritative mutation responses;
- surface stale-version conflicts;
- never report an external WhatsApp handoff as confirmed delivery.

## 9. Security architecture and implemented controls

Security is layered. No single control below is sufficient by itself.

### 9.1 Authentication and credential security

- Tenant users and platform operators use separate guards, routes, token/session tables, and scopes.
- Passwords use Laravel's configured hasher through [`PasswordHasher`](../app/Modules/Auth/Services/Security/PasswordHasher.php); plain passwords are not persisted.
- Minimum password length defaults to 12 and is environment-configurable.
- Access and refresh tokens are opaque random values. Persisted records keep lookup keys and HMAC-SHA-256 digests rather than reusable plaintext secrets; comparisons use `hash_equals`.
- Refresh tokens rotate. Reuse of a non-active refresh token is treated as compromise and revokes the token family/session graph.
- Session and token state is checked for expiry, revocation, active user/tenant access, client validity and organization context.
- Refresh tokens are sent in HttpOnly cookies. Tenant/platform cookie names, paths, domains, Secure and SameSite settings are independently configurable; production defaults Secure on and defaults SameSite to `strict`.
- Tenant and platform login/refresh flows have named rate limiters; account, account+IP and global-IP login attempt windows are configured in [`Auth config`](../app/Modules/Auth/Config/auth.php).
- Sensitive platform actions can require recent authentication through step-up middleware.
- Authentication responses are marked no-store, and exception arguments are disabled globally to reduce secret leakage in stack traces.

### 9.2 Tenant isolation and authorization

- Middleware priority establishes authentication, user, tenant, tenant-access and organization-unit context before route-model binding and authorization. See [`bootstrap/app.php`](../bootstrap/app.php).
- Tenant-owned model queries fail closed without trusted tenant execution context.
- Tenant ownership cannot be reassigned after creation.
- Requests do not trust payload tenant/organization IDs as authority.
- Backend permission middleware and module authorization services are the enforcement point; frontend permission checks are presentation only.
- Tenant plan entitlements can deny disabled features with HTTP 403.
- Organization-unit membership/current context narrows operational access.
- Platform routes additionally enforce platform host/operator/permission policies.

### 9.3 Input, output, and error safety

- Form Requests validate and normalize inputs before domain services run.
- Scoped existence/uniqueness checks prevent cross-tenant relationship injection.
- API Resources control exposed fields and hide stored token digests.
- Correlation IDs connect client-visible errors to server logs.
- Unexpected server exceptions return a generic message; details stay in protected logs.
- Document/phone helpers normalize external identifiers before use, including digits-only WhatsApp destinations.

### 9.4 Financial and stock integrity as security controls

- Database transactions and row locks prevent double allocation, overbooking, duplicate settlement and race-condition corruption.
- Optimistic version checks reject stale updates rather than accepting last-write-wins silently.
- Idempotency records protect replay-sensitive multi-document commands.
- Balanced journals, canonical source identities, unique constraints and posting fingerprints defend against duplicate/misaligned postings.
- Accounting-period rules gate posting/reversal dates.
- Historical transactions use reversals and immutable snapshots, preserving evidence.

### 9.5 Audit and privacy

- Audit recording validates event ownership/context and supports separate tenant/platform read authorization.
- [`AuditPayloadSanitizer`](../app/Modules/Audit/Services/AuditPayloadSanitizer.php) redacts configured password, token, secret, authorization and credential keys and constrains payload depth/size.
- Audit records capture actor/request context without making the request payload authoritative.
- Authentication tokens/digests are hidden from serialized models/resources.
- Tenant documents and branding assets use tenant-aware private storage services; access is not delegated to user-provided paths.

### 9.6 Public document and WhatsApp safeguards

- Public PDF links use Laravel signed URLs with configurable expiry.
- Access revalidates document identity, tenant and lifecycle eligibility; a signature alone is not treated as permanent authority.
- A fresh link can be generated by an authenticated, authorized user.
- WhatsApp URLs are validated before navigation, and sharing remains an explicit browser/user action.
- Manual verification codes are hashed, expire, have maximum attempts, and sit behind rate-limited endpoints.

### 9.7 Security tooling and readiness checks

Available project commands include:

```bash
composer runtime:check
composer production:check
composer security:audit
npm run security:audit
```

`auth:readiness` checks application key, database/schema, cache and required authentication bindings. `platform:health` adds platform/operational health checks. Dependency-audit commands must be run regularly; their existence does not prove the latest audit is clean.

### 9.8 Current environment observations and production gaps

`php artisan about` on 2026-09-18 reported this inspected workspace as:

- `APP_ENV=local`;
- debug mode enabled;
- application timezone UTC;
- MySQL database;
- database cache and queue;
- file sessions;
- log broadcasting;
- public storage link missing;
- config/events/routes not cached.

These are local runtime observations, not intended production guarantees. Before production, at minimum:

- set production environment and disable debug;
- configure/verify the intended business timezone (project convention is Asia/Colombo unless a deployment explicitly requires another timezone);
- enforce externally valid HTTPS, correct `APP_URL`, trusted proxies and secure cookie settings;
- use least-privilege database/storage credentials and protected secrets;
- prove queue workers, scheduler, failed-job handling, cache, mail and private storage are operational;
- create/test backups and restoration;
- rehearse forward-only migrations on a disposable copy of the target schema;
- verify security headers and TLS at the reverse proxy/web server;
- run dependency audits, full automated gates and critical-workflow UAT;
- link public storage only if a required public-storage workflow calls for it; private financial/tenant files must remain private.

Security controls reduce risk but do not constitute penetration testing, compliance certification, or legal assurance.

## 10. Core workflow references

### 10.1 Normal procurement

```text
Purchase Order
  → approval
  → Goods Receipt
  → Inventory receipt + valuation
  → GRNI accounting
  → Supplier Invoice
  → GRNI cleared + Supplier Payable
  → Supplier Payment
  → Payable reduced + Cash/Bank reduced
```

The current supplier-invoice capability requires received quantity. Purchase returns/debit notes use explicit lifecycles, allocations and reversal lineage. Fast Purchase orchestrates the owning modules inside idempotent transaction boundaries.

### 10.2 Invoice and settlement

```text
Source document
  → Invoice snapshot and source allocations
  → approval/posting
  → receivable/payable balance
  → Payment posting and allocation
  → partial/full settlement
  → governed reversal/cancellation when allowed
```

Common invoice states include `draft`, `approved`, `posted`, `partially_paid`, `paid`, `reversed`, `cancelled`, and `void`.

### 10.3 Inventory

```text
available = on hand - reserved - allocated
```

Receipt increases on-hand/valuation; reservation reduces availability without physical issue; allocation makes a stronger commitment; issue reduces on-hand and consumes valuation; transfer coordinates exact outbound/inbound locations; reversal creates linked counter-movements.

### 10.4 Vehicle Service

```text
Draft job
  → inspection/lines/workforce
  → stock reservations
  → Start: exact stock issues + finance effects
  → work completion
  → Service Invoice
  → receipt/allocation
  → partially paid / paid
```

Vehicle Service owns the job; Inventory, Invoice, Payment and Finance retain authority over their respective effects.

## 11. Testing and quality controls

The snapshot contains 179 PHP test files and 96 frontend test/spec files. Counts describe inventory, not a fresh full-suite pass.

Testing layers include:

- module integration/engine tests in `app/Modules/*/Tests`;
- cross-module and feature tests under [`tests`](../tests/);
- frontend component, flow and source-contract tests near their feature code;
- SQLite-oriented fast test execution plus [`phpunit.mysql.xml`](../phpunit.mysql.xml) for MySQL compatibility;
- static TypeScript, ESLint, production build and diff checks.

Standard verification commands:

```bash
php artisan test
composer test:mysql
npm run test
npm run typecheck
npm run lint
npm run build
git diff --check
```

The architecture index records an earlier verified baseline of 669 Laravel tests / 8,354 assertions on both default and MySQL profiles, 69 Vitest files / 256 tests, TypeScript, lint and production build. That baseline predates later September changes and must not be presented as a fresh full-suite result for this snapshot. Recent feature records document focused verification for their own changes.

## 12. Known boundaries and non-implemented scope

The following must not be inferred from nearby code or requirement documents:

- **General Expenses workspace:** a finalized requirement exists, but no standalone Expenses module/workspace is implemented.
- **Consignment/pay-on-sale procurement:** normal company-owned procurement is implemented; supplier-owned custody, sell-through eligibility and consignment settlement/subledgers are not.
- **General item Sales fulfillment:** invoice types include sales, but there is no complete Sales-owned quotation/order/delivery/stock-issue/COGS workflow.
- **Payroll:** HR master/workforce capability exists; payroll scope remains a product decision.
- **WhatsApp automation:** there is no paid API send, delivery confirmation, read receipt or automatic phone-ownership validation.
- **Historical as-of aging:** current balance reporting must not be presented as historical aging unless a dedicated historical balance service supplies it.
- **Cross-organization consolidated dashboard figures:** current authoritative dashboard/report services are scoped to the selected organization unit and do not fabricate consolidation.
- **Vehicle Rental navigation:** domain/routes exist; visibility may remain intentionally limited/hidden in tenant navigation depending on current product configuration.

Approved requirement documents are not implementation evidence. Verify routes, services, migrations, UI and tests before describing a feature as available.

## 13. Deployment and operations checklist

1. Install locked Composer/NPM dependencies.
2. Configure production environment, application key, HTTPS URL, trusted proxies, database, cache, queue, mail, session/cookies and private storage.
3. Run `composer production:check` and both dependency audits.
4. Back up and test restoration.
5. Rehearse migrations against a disposable target-schema copy; resolve conflicts rather than auto-deduplicating financial history.
6. Run migrations and permission/catalog synchronization using the project scripts.
7. Build frontend assets.
8. Run queue workers and scheduler under monitored process supervision.
9. Configure log aggregation/retention and failed-job response.
10. Execute the full verification gates and critical workflow UAT.
11. Verify PDF rendering, private files, signed public links and external HTTPS access.
12. Record the deployed commit, migration state, environment verification and rollback procedure.

The repeatable connected acceptance dataset is documented in [`2026-09-15-full-system-sample-data-flow.md`](changes/2026-09-15-full-system-sample-data-flow.md).

## 14. Source map for future reference

| Question | Start with |
|---|---|
| Which modules are active? | [`bootstrap/providers.php`](../bootstrap/providers.php) |
| How is middleware ordered and how are API exceptions rendered? | [`bootstrap/app.php`](../bootstrap/app.php) |
| What framework/package versions are allowed? | [`composer.json`](../composer.json), [`package.json`](../package.json) |
| What exact dependencies are locked? | [`composer.lock`](../composer.lock), [`package-lock.json`](../package-lock.json) |
| How do frontend pages and guards connect? | [`resources/js/app/router.tsx`](../resources/js/app/router.tsx), [`resources/js/app/access`](../resources/js/app/access/) |
| How is tenant data isolated? | [`TenantOwnedModel`](../app/Modules/Core/Models/TenantOwnedModel.php), [`TenantScope`](../app/Modules/Core/Database/Scopes/TenantScope.php), [`TenantScopedRequest`](../app/Modules/Core/Http/Requests/TenantScopedRequest.php) |
| How does authentication work? | [`Auth`](../app/Modules/Auth/), [`Auth config`](../app/Modules/Auth/Config/auth.php), [`Auth routes`](../app/Modules/Auth/Routes/api.php) |
| Where are permissions enforced? | [`User`](../app/Modules/User/), module route/authorization services, [`RequireTenantPermissionMiddleware`](../app/Modules/User/Http/Middleware/RequireTenantPermissionMiddleware.php) |
| Where are plan features enforced? | [`RequireTenantFeatureMiddleware`](../app/Modules/Tenant/Http/Middleware/RequireTenantFeatureMiddleware.php) |
| How are replay and concurrent writes controlled? | [`IdempotencyService`](../app/Modules/Idempotency/Services/IdempotencyService.php), owning module services and requests |
| Where is audit sanitization? | [`AuditPayloadSanitizer`](../app/Modules/Audit/Services/AuditPayloadSanitizer.php), [`Audit config`](../app/Modules/Audit/Config/audit.php) |
| What owns financial posting? | [`Finance`](../app/Modules/Finance/), [`FinancePostingInterface`](../app/Modules/Finance/Contracts/FinancePostingInterface.php) |
| What owns stock? | [`Inventory`](../app/Modules/Inventory/) |
| What owns commercial documents and settlement? | [`Invoice`](../app/Modules/Invoice/), [`Payment`](../app/Modules/Payment/) |
| What is the current schema catalogue? | [`database/schema-docs/tables.md`](../database/schema-docs/tables.md) |
| What changed recently and why? | newest relevant file in [`docs/changes`](changes/) |
| What still requires release evidence? | [`docs/README.md`](README.md) |

## 15. Maintenance rule

Update this specification when the implemented module catalogue, technical stack, architecture boundary, core workflow, authentication/tenancy model, security control, deployment requirement, or implemented-versus-planned boundary materially changes. Do not rewrite historical change records; append a new record and update this current-state document only when the system snapshot itself changes.
