# Sales Module Audit

Audit date: 2026-06-13

## Scope Reviewed

- `app/Modules/Sales`
- Sales migrations, models, services, DTOs, requests, resources, controllers, routes,
  validators, provider, and tests
- `resources/js/modules/sales`
- Sales dependencies in Invoice, Payment, Inventory, Tax, Item/UOM, Customer,
  Warehouse, Sequence, Finance, and application routing
- Shared Purchase frontend components currently consumed by Sales

## Baseline

- `php artisan test --filter=Sales`: 7 passed, 51 assertions
- TypeScript: `tsc --noEmit` passed
- Production frontend build: passed
- Working tree was clean before this audit document was added
- `php artisan migrate:fresh --seed` was attempted against the configured local MySQL
  database, but `127.0.0.1:3306` refused the connection. No database changes occurred.

## Module Boundaries

Sales owns quotations, sales orders, deliveries, returns, sales credit notes, sales
header adjustments, sales-to-invoice links, and sales status history. It delegates:

- invoice creation, source allocation, balances, and invoice quantity protection to Invoice
- payment persistence and settlement to Payment
- stock allocation, issue, receipt, and reversal to Inventory
- tax snapshots, postings, and return reversals to Tax
- item/UOM conversion to Item and UOM services
- customer data and credit profile ownership to Customer
- document numbering to Sequence

These boundaries are generally sound. No Sales business logic needs to move into Core.

## Large Classes

| Class | Lines | Finding |
| --- | ---: | --- |
| `SalesReturnService` | 573 | Combines validation, source resolution, write persistence, adjustment allocation, approval, posting, inventory, source mutation, replacement dispatch, credit creation, tax reversal, cancellation, and relation loading. |
| `SalesInvoiceIntegrationService` | 461 | Combines source resolution, DTO construction, adjustment conversion, invoice orchestration, link persistence, quantity mutation, and delivery status refresh. |
| `SalesOrderService` | 394 | Combines write persistence, validation, status transitions, quantity mutation, aggregate totals, relation loading, and eligibility rules. |
| `SalesQuotationService` | 280 | Contains quotation writes, validation, statuses, conversion mapping, and relation loading. Large but cohesive around the quotation workflow. |
| `SalesDeliveryService` | 209 | Coordinates validation, persistence, proportional adjustments, inventory, order quantities, tax, posting, and reversal. Large but transactionally cohesive. |
| `SalesOrderController` | 150 | Contains query filtering, eligibility calculations, and manual line response shaping. |

## Large Methods

| Method | Approximate size | Finding |
| --- | ---: | --- |
| `SalesReturnService::create()` | 85 lines | Validation output, calculations, source mapping, and persistence are interleaved. |
| `SalesReturnService::validate()` | 65 lines | Scenario rules, scope rules, source resolution, and quantity rules are combined. |
| `SalesReturnService::sourceLine()` | 85 lines | Resolves three source types into a loosely typed associative structure. |
| `SalesReturnService::allocateAdjustments()` | 60 lines | Resolves source documents, calculates ratios, persists allocations, and mutates source adjustment balances. |
| `SalesReturnService::dispatchReplacement()` | 50 lines | Builds and posts a replacement delivery inside return posting. |
| `SalesInvoiceIntegrationService::normalize()` | 90 lines | Builds six parallel collections and resolves customers and source documents. |
| `SalesInvoiceIntegrationService::appendDelivery()` | 90 lines | Maps delivery lines, pricing, source lines, adjustments, and source metadata. |
| `SalesInvoiceIntegrationService::appendOrder()` | 85 lines | Repeats most delivery mapping with order-specific quantity rules. |
| `SalesOrderService::refreshStatus()` | 45 lines | Calculates status plus allocated, delivered, invoiced, and returned totals. |
| `SalesDeliveryService::create()` | 100 lines | Validation, source resolution, calculation, persistence, and proportional adjustment cloning are combined. |
| `SalesDocumentForm` | 130+ lines in component | Owns document mapping, pricing preview, all form state, payload construction, submission, and all visual sections. |
| `SalesReturnCreatePage` | 206 lines | Owns scenario rules, source loading, manual line editing, table rendering, payload mapping, and submission. |
| `SalesInvoiceCreatePage` | 168 lines | Owns source loading, line state, preview, rendering, payload mapping, and submission. |

## Readability Findings

1. Sales order write, status, and quantity methods are mixed in one service.
2. Sales return source data is represented by a large associative array with repeated
   string keys and nullable values.
3. Sales return source rows are resolved once during validation and again during creation.
4. Sales invoice preparation returns a large associative array carrying both Invoice DTOs
   and later Sales mutation metadata.
5. Source and line type strings are repeated across services, requests, resources, and frontend code.
6. `SalesCalculationService` repeats line aggregation in `calculate()` and `headerAmounts()`.
7. Controller eligibility calculations duplicate quantity rules owned by services.
8. Controller line summaries duplicate line transformation already present in resources.
9. Status-label formatting is repeated in every Sales resource.
10. Request scope rules are repeated outside `SalesDocumentRequest`.
11. `SalesCreditNoteData` is inconsistent with the established `Create*Data` naming.
12. Frontend date creation is repeated across forms/pages.
13. `SalesDocumentForm` directly imports Purchase-specific line, adjustment, and summary components.
14. Several frontend action rules are repeated inline in list/detail pages.

## Maintainability Findings

1. Delivery, invoice, and return workflows depend on the entire `SalesOrderService` to
   update quantities.
2. Invoice creation and preview share normalization, but normalization also contains
   persistence-update metadata and source status behavior.
3. Return posting cannot be changed independently from return creation and validation.
4. Return source resolution and source mutation are hidden inside a broad service.
5. Sales return adjustment balances are mutated while a return is still draft.
6. Cancelling a draft/approved return does not release its adjustment allocations.
7. Two valid draft returns can target the same remaining source quantity; posting does
   not revalidate the latest remaining quantity.
8. Reversing the only delivery resets line quantities but leaves the order status as
   `delivered`, because the aggregate refresh defaults to the current status when all
   workflow quantities return to zero.
9. Sales delivery tax posting has no corresponding reversal call in the delivery reversal
   workflow.
10. Delivery, return, and credit-note list pages submit `search`, but their controllers
    do not apply it.
11. Direct sales-order invoicing does not enforce an order status in Sales integration;
    this is existing behavior and requires a business decision before changing.
12. Invoice source quantity checks are sequentially safe through Invoice-owned allocation
    queries, but concurrent source-row locking is not visible.
13. Organization validation allows tenant-global references and can accept an
    organization-specific reference when the request organization is null. Reads use
    exact organization scoping.

## Resource And DTO Findings

- No Sales resource performs service-container lookup or monetary business calculation.
- Resource relation summaries are lightweight transformations and are appropriate.
- Status-label formatting should be centralized in the existing resource concern.
- Order and delivery line arrays should move into focused resources so controllers and
  parent resources do not maintain parallel shapes.
- All Sales DTOs are already `final readonly` and have no side effects.
- `SalesCreditNoteData` should be renamed to `CreateSalesCreditNoteData`.
- The invoice normalization result should become a readonly prepared-data DTO.
- Return source resolution should return a readonly DTO instead of an associative array.
- `SalesPostingResult::$invoiceId` is unused by current Sales callers.

## Request And Controller Findings

- HTTP validation correctly protects request shape and decimal formats.
- Services correctly retain domain validation for non-HTTP callers.
- Tenant and organization IDs are accepted consistently.
- Common scope rules and nullable scalar conversion are duplicated.
- `ListSalesRequest::status` accepts any string rather than known Sales statuses.
- Controllers are thin for commands, but list filtering and line lookup behavior remain embedded.
- Delivery, return, and credit-note search inputs currently have no backend effect.
- Invoice and return pages mostly expose backend errors through a general alert, but do
  not consistently map field-level validation errors.

## Frontend Findings

Large files:

- `salesTypes.ts`: 319 lines
- `SalesDocumentForm.tsx`: 244 lines
- `SalesReturnCreatePage.tsx`: 206 lines
- `salesApi.ts`: 185 lines
- `SalesInvoiceCreatePage.tsx`: 168 lines
- `SalesDocumentListPage.tsx`: 146 lines
- `SalesDeliveryCreatePage.tsx`: 136 lines

Complexity and coupling:

- `SalesDocumentForm` contains header, line, adjustment, summary, preview, payload, and
  submission concerns.
- Sales uses Purchase-specific editor types and components directly. The UI behavior is
  useful, but the module boundary is undocumented and names leak Purchase concepts.
- Sales invoice and return source loading is embedded in pages and has no explicit loading state.
- Invoice source add/preview operations can be triggered repeatedly while requests are active.
- Sales return source loading can leave stale errors and gives no loading feedback.
- Action-capability conditions are repeated in document and return lists.
- API result types for invoice creation/preview, payment preparation, and return posting
  use broad `Record<string, unknown>` contracts.

## Decimal Safety Findings

- Backend monetary and quantity calculations consistently use `DecimalMath`.
- Database monetary and quantity columns use scale 6.
- Frontend document preview uses shared string-decimal utilities.
- No `parseFloat()`, `toFixed()`, `Math.round()`, `Math.floor()`, or `Math.ceil()` is used
  in Sales business calculations.
- `Number()` is used only for route/query/resource IDs, not business decimal values.
- The client preview duplicates backend rules for user feedback; backend totals remain authoritative.

## Test Findings

Strengths:

- Quotation totals and quotation-to-order conversion are covered.
- Delivery allocation, inventory issue, and service-item behavior are covered.
- Multiple deliveries, partial invoices, follow-up invoices, and total adjustment
  allocation are covered.
- Referenced, manual, credit-only, inventory-only, warranty, exchange, damaged/quarantine,
  and imported return scenarios are covered in one broad workflow test.
- Tenant isolation, over-delivery, over-invoicing, over-return, and missing UOM conversion
  are covered.
- Tax owns partial sales-return reversal coverage.

Gaps:

- No explicit sequential double-invoice rejection test exists in Sales.
- No test verifies order state after delivery reversal.
- No test verifies cancelled returns release source adjustment balances.
- No test verifies posting a stale second draft return is rejected.
- Payment preparation is covered, but customer invoice allocation is not exercised through
  a Sales-specific workflow.
- No API test covers Sales resources, search behavior, validation, or tenant scope.
- No frontend component-test infrastructure exists.

## Workflow Verification Assessment

| Workflow | Current evidence |
| --- | --- |
| Quotation -> Sales order | Covered. |
| Sales order -> Delivery | Covered, including stock and service lines. |
| Delivery/order -> Invoice | Delivery source is covered; direct order source exists but lacks a focused test. |
| Partial invoicing | Covered across multiple deliveries and follow-up invoice. |
| Double-invoice prevention | Invoice allocation checks prior quantity; explicit Sales coverage is missing and concurrent locking remains a risk. |
| Payment preparation | Covered as a Payment DTO boundary. |
| Referenced return | Covered. |
| Manual customer return | Covered. |
| Credit note only | Covered. |
| Inventory adjustment only | Covered. |
| Warranty replacement | Covered. |
| Exchange return | Covered. |
| Damaged/quarantine return | Covered. |

## Refactor Plan

The cleanup will remain scoped and behavior-preserving except for confirmed defects:

1. Split sales-order write, status, and quantity responsibilities behind the existing
   `SalesOrderService` API.
2. Split sales invoice DTO preparation and quantity updates from the integration
   orchestrator, using a readonly prepared-data DTO.
3. Split sales return source interaction, write behavior, and posting behavior behind
   the existing `SalesReturnService` API.
4. Revalidate and lock return sources during posting to prevent stale double returns.
5. Release return adjustment reservations on cancellation.
6. Correct order aggregate status after full delivery reversal.
7. Extract repeated sales calculation aggregation.
8. Centralize request scope/scalar helpers and resource status formatting.
9. Move line eligibility and transformation out of controllers.
10. Implement the currently ignored delivery, return, and credit-note search filters.
11. Split `salesApi.ts` by workflow while retaining a compatibility barrel.
12. Split `SalesDocumentForm` into focused visual sections and isolate editor coupling.
13. Extract invoice/return source loading state into focused hooks/helpers.
14. Add focused regression and API tests, then run requested and repository-wide checks.

## Explicit Non-Goals

- No database schema changes
- No Core-module business logic
- No event-driven flow
- No new framework
- No redesign of Invoice, Payment, Inventory, Tax, Customer, or Sequence ownership
- No change to direct-order invoice eligibility without an explicit business rule
- No replacement of backend decimal calculations with frontend calculations
- No broad redesign of the shared Purchase/Sales editor system

## Refactors Performed

Backend:

- Split `SalesOrderService` into a stable facade plus:
  - `SalesOrderWriteService`
  - `SalesOrderStatusService`
  - `SalesOrderQuantityService`
- Fixed the confirmed full-delivery reversal bug: when all delivered, allocated,
  invoiced, and returned quantities return to zero, a previously delivered order is
  restored to `approved` instead of staying `delivered`.
- Split `SalesInvoiceIntegrationService` into a thin orchestrator plus:
  - `SalesInvoiceDtoFactory`
  - `SalesInvoiceQuantityUpdater`
  - `PreparedSalesInvoiceData`
- Locked sales invoice source documents during real invoice creation while keeping
  previews read-only.
- Split `SalesReturnService` into a stable facade plus:
  - `SalesReturnWriteService`
  - `SalesReturnPostingService`
  - `SalesReturnSourceService`
  - `SalesReturnAdjustmentService`
  - `ResolvedSalesReturnSource`
- Fixed confirmed return adjustment reservation leakage by releasing reserved header
  adjustment balances when a draft/approved sales return is cancelled.
- Fixed confirmed stale-return posting risk by revalidating and locking return source
  rows during posting.
- Added aggregate validation for duplicate invoice-line references within the same
  sales return.
- Centralized Sales list search/date/status/customer filters in `FiltersSalesQueries`.
- Implemented ignored backend `search` behavior for deliveries, returns, and credit
  notes.
- Centralized request tenant/organization rules and scalar helpers in `SalesRequest`.
- Centralized resource status-label formatting in `FormatsSalesResources`.
- Extracted nested line/allocation transformers into focused resources:
  - `SalesOrderLineResource`
  - `SalesOrderLineLookupResource`
  - `SalesDeliveryLineResource`
  - `SalesQuotationLineResource`
  - `SalesReturnLineResource`
  - `SalesReturnAdjustmentAllocationResource`
  - `SalesReturnableDeliveryLineResource`

Frontend:

- Split `salesApi.ts` into workflow files while keeping `salesApi.ts` as a compatibility
  barrel:
  - `salesQuotationApi.ts`
  - `salesOrderApi.ts`
  - `salesDeliveryApi.ts`
  - `salesInvoiceApi.ts`
  - `salesReturnApi.ts`
- Moved API-only response shapes into `salesTypes.ts`.
- Typed customer invoice creation with the Invoice module's `Invoice` type instead of
  `Record<string, unknown>`.
- Split `SalesDocumentForm` into:
  - `SalesDocumentHeaderSection`
  - `SalesDocumentLinesSection`
  - `SalesDocumentAdjustmentSection`
  - `SalesDocumentSummarySection`
  - `salesDocumentFormUtils`
- Isolated the intentional reuse of Purchase line/adjustment/summary components behind
  Sales-named wrappers.
- Added explicit source-loading and preview-loading guards to the invoice creation page.
- Added explicit source-loading handling and field-level backend validation mapping to
  the return creation page.
- Removed the unnecessary `Number()` conversion for created invoice IDs.

## Duplicate Logic Removed

- Sales order write/status/quantity behavior no longer shares one large service.
- Sales invoice DTO preparation and quantity mutation no longer share one integration
  service.
- Sales return source resolution and source mutation no longer duplicate array-shaped
  source metadata across validation and creation.
- Return adjustment allocation and release accounting is centralized.
- Status-label formatting is centralized in one resource concern.
- Sales list search, status, customer, and date filters are centralized.
- Request tenant/organization rules and nullable scalar conversion are centralized.
- Parent resources no longer contain long inline line/allocation maps.
- Sales document form totals, document mapping, and date defaults are isolated from the
  visual component.

## Tests Improved

Added regression coverage for:

- Reversing a full delivery reopens the sales order to `approved`.
- Cancelling a draft/approved sales return releases reserved header adjustment balances.
- Posting a stale second draft return is rejected against the latest source quantity.
- Attempting to invoice the same fully invoiced sales delivery source twice is rejected.

Sales workflow coverage after cleanup:

| Workflow | Result |
| --- | --- |
| Quotation -> Sales order | Passing |
| Sales order -> Delivery | Passing |
| Delivery/order -> Invoice | Delivery source passing; direct order source remains existing behavior |
| Partial invoicing | Passing |
| Double-invoice prevention | Passing sequential coverage |
| Payment preparation | Passing |
| Referenced return | Passing |
| Manual customer return | Passing |
| Credit note only | Passing |
| Inventory adjustment only | Passing |
| Warranty replacement | Passing |
| Exchange return | Passing |
| Damaged/quarantine return | Passing |

## Verification Results

After cleanup:

- `php artisan test --filter=Sales`: passed, 11 tests, 61 assertions
- `php artisan migrate:fresh --seed`: passed
- `npm run typecheck`: passed
- `npm run build`: passed
- `git diff --check`: passed
- PHP lint over Sales HTTP, service, and DTO files: passed

## Remaining Risks

- `SalesInvoiceDtoFactory`, `SalesReturnWriteService`, `SalesReturnSourceService`, and
  `SalesQuotationService` are still large, but each is now more cohesive. Further
  splitting should be driven by concrete change pressure.
- Direct sales-order invoice eligibility remains unchanged. Adding status restrictions
  would be a business-rule change.
- Sales delivery tax reversal is still not implemented because there is no confirmed
  Tax API for reversing posted sales delivery tax snapshots.
- Frontend Sales invoice and return pages are more guarded but remain page-heavy.
  Further extraction should be paired with component tests or a shared source-loading
  pattern.
- `SalesCreditNoteData` remains as-is to avoid broad rename churn; it can be renamed to
  `CreateSalesCreditNoteData` in a small follow-up.
- API tests for Sales list search/resource shapes/validation are still absent.

## Before vs After Summary

Before:

- `SalesOrderService`, `SalesInvoiceIntegrationService`, and `SalesReturnService` were
  broad multi-responsibility services.
- Return cancellation leaked reserved adjustment balances.
- Return posting trusted draft-time source quantities.
- Full delivery reversal left the order status stale.
- Delivery, return, and credit-note search parameters were accepted but ignored.
- `salesApi.ts` and `SalesDocumentForm` mixed multiple workflows in one file.

After:

- Public service APIs remain stable, but write/status/quantity/source/posting concerns
  are easier to inspect and test.
- Confirmed behavior bugs are covered by focused tests.
- Resources and controllers are lighter and more consistent.
- Request scoping and list filtering are centralized.
- Frontend API calls are organized by workflow, and Sales document form sections are
  named around the Sales UI instead of leaking Purchase names through the main form.
