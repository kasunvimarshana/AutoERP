# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added (Vue 3 SPA + security hardening — 2026-02-20)
- **Vue 3 SPA frontend** — full single-page application using Vue 3 (Composition API + `<script setup>`),
  Pinia state management, and Vue Router 4 for client-side navigation.
  - `resources/js/App.vue` — root component with `<RouterView>`
  - `resources/js/router/index.js` — route definitions with auth guard (redirects unauthenticated users to `/login`)
  - `resources/js/stores/auth.js` — Pinia auth store: JWT token lifecycle, `login()`, `logout()`, `fetchMe()`, `refresh()`
  - `resources/js/composables/useApi.js` — shared Axios instance with `Authorization: Bearer` interceptor and 401 auto-redirect
  - `resources/js/pages/` — LoginPage, DashboardPage (KPI stat cards), ProductsPage, OrdersPage, InvoicesPage, UsersPage, InventoryPage
  - `resources/js/components/AppLayout.vue` — sidebar + header shell with navigation
  - `resources/js/components/StatCard.vue` — reusable KPI card component
  - `resources/views/app.blade.php` — SPA entry point (mounts `#app`), Vite assets loaded conditionally
  - `routes/web.php` — catch-all route forwards all non-API paths to `app.blade.php`
  - `vite.config.js` — updated with `@vitejs/plugin-vue` and `@` alias
  - `package.json` — added `vue ^3.5`, `vue-router ^4.4`, `pinia ^2.2`, `@vitejs/plugin-vue ^5.2` dependencies
- **ForceHttps middleware** — `app/Http/Middleware/ForceHttps.php` enforces HTTPS via 301 redirect and
  attaches `Strict-Transport-Security` (HSTS) header on all secure responses.
  Controlled by `FORCE_HTTPS` env var (default `false` for local dev, set `true` in production).
  Registered as global middleware via `bootstrap/app.php`.
  5 feature tests in `tests/Feature/ForceHttpsTest.php`.
- **Cache layer for business settings** — `BusinessSettingService` now uses a two-tier cache:
  1. Per-request in-memory map (unchanged behaviour — no N+1 within a request)
  2. Laravel cache store (Redis / DB, configurable via `CACHE_STORE`) with a configurable TTL
     (`SETTINGS_CACHE_TTL_SECONDS`, default 300 s) to avoid repeated database hits across requests.
  Cache is automatically invalidated on `set()` and `delete()` calls.
- **ADR-007** — `docs/adr/007-vue3-spa-api-first.md` documents the Vue 3 + Pinia + Vue Router
  architecture decision, JWT storage rationale, route protection strategy, and security considerations.
- `config/app.php` — new `force_https` key driven by `FORCE_HTTPS` env var.
- `.env.example` — added `FORCE_HTTPS` and `SETTINGS_CACHE_TTL_SECONDS` variables.

### Changed
- **`bootstrap/app.php`** — `ForceHttps` added as a global prepended middleware and registered as
  `force.https` alias for optional per-route use.
- **`MODULE_STATUS.md`** — updated test count (167), added Vue 3 / ForceHttps / Cache rows;
  security checklist HTTPS item now marked ✅.

---

### Added (feature gap analysis — 2026-02-20)
- **Stock Transfer module** — inter-warehouse stock transfers with a full 4-state lifecycle (draft → in_transit → received / cancelled).
  Dispatch records `transfer_out` movements on the source warehouse; receive records `receipt` on the destination; cancel reverses dispatched stock.
  Includes: `StockTransfer` + `StockTransferLine` models, migration, `StockTransferService`, `StockTransferController`, 5 API endpoints, 7 feature tests.
  Auto-generates `TRF-XXXXXX` reference numbers via existing `ReferenceNumberService`.
- **Business Settings module** — tenant-scoped runtime key-value configuration store enabling user-configurable system settings (company name, timezone, currency, etc.) without code edits.
  Includes: `BusinessSetting` model, migration, `BusinessSettingService` (in-memory request cache, bulk upsert), `BusinessSettingController`, 3 API endpoints, 5 feature tests.
- **Invoice Schemes module** — configurable invoice/reference-number formatting (prefix, suffix, zero-padding, start number, default flag per scheme type).
  Includes: `InvoiceScheme` model (with `format(int $counter): string`), migration, `InvoiceSchemeService` (exclusive default management, next-number generation via `ReferenceCount`), `InvoiceSchemeController`, 5 API endpoints, 5 feature tests.
- **OpenAPI 3.1 spec updated** — new tags and path entries for all three new modules (Settings, Invoice Schemes, Stock Transfers).
- **RBAC seeder updated** — 9 new permissions: `stock_transfers.*` (5), `invoice_schemes.*` (4), `settings.edit`.

### Changed
- `RbacSeeder` — `settings.update` replaced by `settings.edit` + both retained for back-compat.
- **MODULE_STATUS.md** — updated test count (162), added three new module rows.

---

  Clients may include an `Idempotency-Key: <uuid>` header on any POST, PUT, or PATCH request.
  The server stores the result in the new `idempotency_keys` table and replays it for duplicate
  requests, preventing double-processing of orders, payments, invoices, and other mutations.
  Keys expire after 24 hours (configurable via `IDEMPOTENCY_TTL_HOURS`).
  - `app/Http/Middleware/IdempotencyMiddleware.php`
  - `app/Models/IdempotencyKey.php`
  - `database/migrations/2026_02_20_000001_create_idempotency_keys_table.php`
  - **7 new tests** in `tests/Feature/IdempotencyTest.php`
- **OpenAPI 3.1 specification** — `docs/openapi.yaml` documents all 70+ API endpoints with
  request/response schemas, security definitions, idempotency header parameters, and tag groupings.
- **ADR-006** — `docs/adr/006-idempotency-keys.md` documents the design decision, protocol,
  storage schema, and alternatives considered for HTTP idempotency support.

### Changed
- **`bootstrap/app.php`** — `IdempotencyMiddleware` appended to the `api` middleware group and
  registered as an alias (`idempotency`) for optional per-route use.
- **`MODULE_STATUS.md`** — Updated test count (143), API Docs status (✅), added Idempotency Keys row and security checklist entry.

---

- **`PricingEngine::applyConditionalRule()`** — resolves and applies conditional pricing adjustments.
- **Batch / Lot / Serial / Expiry tracking** on `stock_movements` — new columns: `batch_number`, `lot_number`, `serial_number`, `expiry_date`, `valuation_method`.
- **`stock_batches` table** — append-only cost-layer table for FIFO/FEFO valuation; each inbound movement creates one row; outbound movements deplete the oldest (FIFO) or nearest-expiry (FEFO) batches first.
- **`StockBatch` Eloquent model** with full relationship definitions.
- **FIFO / FEFO depletion logic** in `InventoryService::depleteBatches()` — FEFO orders by `expiry_date ASC NULLS LAST`, FIFO by `received_at ASC`.
- **`InventoryService::getLowStockItems()`** — returns stock items at or below their reorder point.
- **`InventoryService::getExpiringBatches()`** — returns batches expiring within a configurable day window.
- **`InventoryService::getFifoCost()`** — computes the weighted-average unit cost from open FIFO layers.
- **`InventoryController`** with four new API endpoints:
  - `GET /api/v1/inventory/stock` — paginated stock levels
  - `GET /api/v1/inventory/alerts/low-stock` — items at/below reorder point
  - `GET /api/v1/inventory/alerts/expiring?days=N` — expiring batch alerts
  - `GET /api/v1/inventory/fifo-cost` — FIFO cost lookup
- **Updated `InventoryServiceInterface`** with signatures for the new alert and cost methods.
- **12 new tests** covering Conditional pricing rules (flat delta, percentage, fixed) and all four inventory alert / cost API endpoints.
- **Updated README.md** with project-specific architecture overview, module table, API reference, and quick-start guide (replaces default Laravel README).

### Changed
- `InventoryService::adjust()` — extended signature to accept `batchNumber`, `lotNumber`, `serialNumber`, `expiryDate`, and `valuationMethod` parameters; creates a `StockBatch` row on inbound movements and depletes batches on outbound movements.
- `StockMovement::$fillable` — added `batch_number`, `lot_number`, `serial_number`, `expiry_date`, `valuation_method`.
- `StockMovement::casts()` — added `expiry_date => date` cast.

---

## [v1.0.0] — 2026-02-19

### Added
- Full multi-tenant, hierarchical multi-organisation SaaS ERP/CRM platform.
- **Platform layer**: Tenant CRUD, activate/suspend.
- **Auth**: JWT login/logout/refresh/me via `tymon/jwt-auth`; brute-force throttle on `/auth/login`.
- **Organisation**: Nested-set hierarchy with `/organizations/tree` endpoint.
- **User**: CRUD, suspend/activate, Spatie RBAC.
- **RBAC**: Roles, permissions, `RbacSeeder`, policy gate via `ProductPolicy`, `OrderPolicy`, `InvoicePolicy`.
- **Product**: Goods, Service, Digital, Bundle, Composite types; SKU/barcode; variants; categories; units; optimistic locking.
- **Pricing Engine**: Flat, Percentage, Tiered, Rule-Based price lists and rules; min/max quantity thresholds; priority-based rule resolution.
- **Inventory**: Multi-warehouse `StockItem` ledger; `StockMovement` append-only log; pessimistic locking.
- **Order**: Draft → Confirmed → Cancelled; BCMath totals; `OrderCreated` event.
- **Invoice**: Draft → Sent → Void; `InvoiceCreated` event.
- **Payment**: Multi-method payment recording; `PaymentRecorded` event.
- **POS**: Cash-register sessions, POS transactions, `PosTransactionStatus` enum.
- **POS Returns**: Full/partial return and refund workflow.
- **Purchase/Procurement**: Supplier purchase orders with line items; `PurchaseStatus` enum.
- **Expense**: Expense categories and records per business location.
- **Stock Adjustment**: Audited manual adjustments with `StockAdjustmentReason` enum.
- **CRM**: Contacts, leads, follow-ups, pipeline stages.
- **HR**: Employee records, leave requests, payroll basics.
- **Accounting**: Chart of accounts, journal entries, accounting periods.
- **Reporting**: Sales summary, inventory summary, receivables, top products, POS summary, purchase summary, expense summary.
- **Notification**: In-app notifications; `NotificationChannel` and `NotificationStatus` enums.
- **File Manager**: File upload, folder management, `FileDisk` enum.
- **Audit Log**: `AuditService` + `AuditEventSubscriber` for automatic immutable audit trail.
- **Webhook**: Configurable webhooks; `DeliverWebhookJob` queued retry; `WebhookEventSubscriber`.
- **Brand, Customer Group, Business Location, Payment Account, Variation Templates, Currency, Selling Price Groups** modules.
- **Repository Pattern**: `BaseRepository`, `ProductRepository`, `OrderRepository`, `RepositoryServiceProvider`.
- **Domain Events**: `OrderCreated`, `InvoiceCreated`, `PaymentRecorded`, `ProductCreated`, `StockAdjusted`.
- **CI/CD**: GitHub Actions workflows for PHPUnit tests (SQLite in-memory) and Laravel Pint linting.
- **124 passing tests** across Feature and Unit test suites.

---

[Unreleased]: https://github.com/kasunvimarshana/AutoERP/compare/v1.0.0...HEAD
[v1.0.0]: https://github.com/kasunvimarshana/AutoERP/releases/tag/v1.0.0
