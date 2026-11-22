# Purchase Module Audit

Audit date: 2026-06-13

## Scope Reviewed

- `app/Modules/Purchase`
- Purchase migrations, models, services, DTOs, validators, requests, resources, controllers, routes, and tests
- `resources/js/modules/purchase`
- Purchase callers and dependencies in Invoice, Payment, Inventory, Tax, Item, Supplier, Reporting, and application routing
- Cross-module API coverage in `tests/Feature/Api/CoreModulesApiTest.php`

## Baseline

- Purchase tests: 19 passed, 83 assertions
- Purchase/GRN cross-module API test: 1 passed, 9 assertions
- TypeScript: `tsc --noEmit` passed
- Working tree was clean before this audit document was added

## Module Boundaries

The Purchase module owns purchase orders, goods receipt notes, purchase returns,
purchase debit notes, purchase header adjustments, and purchase-to-invoice links.
It delegates:

- stock movements and adjustments to Inventory
- invoice persistence, source allocation, balances, and duplicate quantity checks to Invoice
- payment persistence and settlement to Payment
- tax snapshots and reversals to Tax
- UOM conversion to Item/UOM services
- finance posting validation to Finance

These boundaries are generally sound. Business logic should remain in the owning
module; no Purchase logic needs to move into Core.

## Large Classes

| Class | Lines | Finding |
| --- | ---: | --- |
| `PurchaseOrderService` | 415 | Mixes write operations, validation, status transitions, line quantity mutation, aggregate status calculation, relation loading, and persistence mapping. |
| `PurchaseInvoiceIntegrationService` | 405 | Mixes source lookup, tenant checks, purchase-to-invoice DTO construction, adjustment conversion, invoice persistence orchestration, link persistence, line quantity mutation, and GRN status calculation. |
| `PurchaseReturnService` | 282 | Contains both referenced and manual return creation plus approval, posting, inventory, debit-note, tax, and status orchestration. Large, but still centered on one return workflow. |
| `GoodsReceiptNoteService` | 245 | Contains GRN validation, creation, PO adjustment allocation, posting, reversal, inventory, tax, and PO quantity orchestration. Large, but cohesive enough for focused method extraction rather than a broad split. |
| `PurchaseOrderCalculationService` | 212 | Repeats line aggregate calculation in `calculate()` and `headerAdjustmentAmounts()`. |
| `PurchaseOrderController` | 145 | Query filtering and line eligibility calculations are embedded in the controller. |

## Large Methods

| Method | Approximate size | Finding |
| --- | ---: | --- |
| `PurchaseInvoiceIntegrationService::normalizeInvoiceData()` | 130 lines | Builds six parallel collections and resolves GRN sources inline. |
| `PurchaseInvoiceIntegrationService::appendPurchaseOrderSource()` | 95 lines | Duplicates most GRN source mapping behavior with PO-specific field names. |
| `GoodsReceiptNoteService::create()` | 115 lines | Validation, source resolution, calculation, proportional allocation, and persistence are interleaved. |
| `PurchaseReturnService::create()` | 110 lines | Manual and referenced line creation branches duplicate persistence shape. |
| `PurchaseReturnService::post()` | 60 lines | Coordinates inventory, GRN/PO quantities, debit notes, and tax in one transaction. |
| `PurchaseOrderService::validateOrderData()` | 65 lines | Reference validation, numeric validation, duplicate detection, and percentage rules are combined. |
| `PurchaseOrderService::replaceLinesAndAdjustments()` | 45 lines | Dense persistence mapping with repeated UOM and calculation calls. |
| `PurchaseOrderForm::calculatePreview()` | 35 lines | Duplicates backend monetary rules for UI preview. Backend remains authoritative. |
| `PurchaseInvoiceCreatePage::addSource()` | 45 lines | Fetching, source-specific mapping, state mutation, and error handling are combined. |

## Readability Findings

1. `PurchaseOrderService` has a visible indentation defect on `adjustment_total`.
2. Purchase source types and line types are repeated as magic strings across services, requests, resources, controllers, and frontend code.
3. Resource helpers for enum values, labels, and relation summaries are duplicated.
4. Request helpers for nullable integers and strings are duplicated.
5. `PurchaseInvoiceIntegrationService` returns a large undocumented associative structure from a normalization method.
6. Several methods mutate related models implicitly, especially `applyReceived()`, `applyInvoiced()`, and `applyReturned()`.
7. Frontend `today()` and decimal fallback helpers are repeated in most forms.
8. Purchase order action capability rules are duplicated between list, detail, and action components.
9. Several frontend tables and action blocks are compressed into difficult one-line JSX.
10. `ListPurchaseOrderRequest` is used for GRNs, returns, and debit notes despite its workflow-specific name.

## Maintainability Findings

1. Purchase order write, status, and quantity behavior cannot be changed independently.
2. Purchase invoice preview and creation share normalization, but normalization also carries persistence update metadata in loosely typed arrays.
3. Quantity updates are not isolated from invoice DTO construction.
4. Purchase resources are inconsistent: some extend `ModuleResource`, while line and adjustment resources extend `JsonResource`.
5. `PurchaseOrderResource` resolves `DecimalMath` from the service container and performs aggregate calculation during serialization.
6. Controllers repeat relation lists and transform eligible lines themselves.
7. Request validation protects shape, while service validation protects tenant/organization references. This split is appropriate, but shared request scope rules are repeated.
8. Frontend types and API calls are centralized into two large files, so unrelated workflows change together.
9. Purchase-specific frontend components are imported by Sales, creating an undocumented UI coupling.
10. Purchase number generation uses `count() + 1`; unique database constraints catch collisions, but concurrent generation can fail and deleted rows can cause reused candidates. This is an existing design risk and is not changed in this cleanup.

## Resource Findings

- Confirmed service-container lookup and business calculation in `PurchaseOrderResource::quantitySum()`.
- Status labels and relation summaries are acceptable transformations but are duplicated.
- GRN and return line shaping is embedded in parent resources; it is readable today but will become costly if line contracts grow.
- No resource should query data or call domain services.

## DTO Findings

- All Purchase DTOs are already `final readonly` and have no side effects.
- `Create*` naming is consistent for major workflows.
- `PurchaseDebitNoteData` is inconsistent with the other creation DTO names.
- `PurchasePostingResult` has unused `invoiceId` capability in current Purchase callers.
- The invoice normalization result is an associative array and should be a readonly DTO.
- DTO arrays rely on PHPDoc for element types; runtime validation remains necessary at module boundaries.

## Request Findings

- Tenant and organization IDs are consistently accepted through `TenantScopedRequest`.
- Services consistently validate referenced records against tenant and organization scope.
- Common scope rules are repeated in every request.
- Nullable scalar conversion is repeated and inconsistent across requests.
- `StorePurchaseReturnRequest` accepts source line types that the current referenced-return service rejects. This is broader validation than implemented behavior.
- `audit_metadata` is read by `toData()` but has no explicit validation rule.
- Service validation duplicates some numeric validation intentionally for non-HTTP callers; removing it would weaken the backend source of truth.

## Frontend Findings

Large files:

- `PurchaseOrderLineEditor.tsx`: 287 lines
- `PurchaseHeaderAdjustmentEditor.tsx`: 259 lines
- `PurchaseOrderForm.tsx`: 209 lines
- `PurchaseInvoiceCreatePage.tsx`: 192 lines
- `PurchaseOrderTabs.tsx`: 186 lines

Complexity and duplication:

- Line and adjustment drawer forms can be extracted without changing state ownership.
- Purchase invoice source loading has two similar source mapping branches.
- Date and decimal normalization helpers are duplicated.
- List pages duplicate pagination, search, status, and action patterns.
- Purchase order calculation is duplicated on the client for preview. This is acceptable only as a preview; submitted totals must continue to come from the backend.
- Invoice validation error paths are source-indexed by the backend but some fields are rendered with line indexes.
- Query-string source IDs are generated by detail pages but create forms do not consume them.

Dead or unused frontend code:

- `PurchaseDocumentTabs.tsx` has no callers.
- `PurchaseLinkedDocumentsTab.tsx` has no callers.
- `InvoiceLookupSelect` and its local `searchInvoices()` helper have no callers.
- `emptyHeaderAdjustment` is unnecessarily imported by `PurchaseOrderForm`.
- The `purchaseOrders` collection returned from invoice normalization is populated but never read.

## API Findings

- `purchaseApi.ts` contains orders, GRNs, returns, invoices, payments, debit notes,
  inventory adjustments, supplier mappings, and lookup adapters.
- The file should become a compatibility barrel over workflow-focused API modules.
- There are no Purchase-specific React query hooks; pages use the shared `useApi` hook directly.
- API response types for invoice creation, preview, payment preparation, and inventory
  adjustment use `Record<string, unknown>`, limiting compile-time safety.

## Test Findings

Strengths:

- PO totals, UOM conversion, partial GRN inventory, multiple GRNs, multiple invoices,
  returns, debit notes, tenant isolation, and preparation boundaries are covered.
- Decimal-sensitive totals have API coverage.
- Adjacent Invoice and Payment modules own source-allocation and payment-allocation tests.

Gaps:

- No explicit sequential double-invoice rejection test exists in Purchase.
- No test directly proves PO partial GRN -> partial invoice -> supplier payment preparation.
- No request test covers organization isolation for invoice, GRN, and return sources.
- No resource test protects purchase order quantity aggregates.
- Test setup is duplicated between `PurchaseEngineTest` and `PurchaseOrderApiTest`.
- There is no frontend component test infrastructure in the repository.

## Workflow Verification Assessment

| Workflow | Current evidence |
| --- | --- |
| PO -> Partial GRN -> Partial Invoice -> Payment | Components exist; partial GRN/invoice and payment preparation are separately tested. Needs one focused Purchase workflow test. |
| PO -> Multiple GRNs | Covered by `receiveOrderInTwoParts()`. |
| GRN -> Multiple Invoices | Covered by `test_one_grn_can_be_invoiced_by_many_supplier_invoices()`. |
| Purchase Return -> Inventory Impact | Covered. |
| Purchase Return -> Supplier Credit | Implemented as Purchase debit note creation and covered. |
| Advance Payment -> Supplier Invoice Allocation | Owned and covered by Payment module, though its generic test uses inbound/customer direction rather than a supplier-specific Purchase path. |
| 3-Way Matching | No explicit matching service, tolerance rules, match status, or UI exists. Current code only enforces source quantity limits and carries source prices. |
| Double Invoice Prevention | Invoice source allocation checks previously invoiced quantity. Sequential behavior should receive explicit Purchase coverage; concurrent locking remains a risk. |

## Refactor Plan

The cleanup will remain behavior-preserving and avoid schema or architecture changes:

1. Split purchase order write, status, and quantity responsibilities behind the existing `PurchaseOrderService` facade.
2. Split purchase invoice DTO preparation and quantity updates from integration orchestration, using a readonly prepared-data DTO.
3. Remove resource service-container access by loading quantity aggregates before serialization.
4. Add a small Purchase resource base for repeated transformation helpers.
5. Add a small Purchase request base for repeated scope and scalar conversion helpers.
6. Split frontend API calls by workflow while retaining `purchaseApi.ts` as a compatibility barrel.
7. Extract the two largest drawer forms and shared form value helpers.
8. Remove confirmed unused frontend components/helpers.
9. Add focused workflow and duplicate-invoice tests, then run Purchase, Invoice, Payment,
   Tax integration, typecheck, and production build verification.

## Explicit Non-Goals

- No database schema changes
- No Core-module business logic
- No event-driven flow
- No new framework
- No new 3-way matching rules
- No change to number generation behavior
- No redesign of Invoice, Payment, Inventory, Tax, or Finance ownership

## Post-Refactor Outcome

### Backend

- `PurchaseOrderService` is now a 98-line compatibility facade over focused write,
  status, and quantity services.
- `PurchaseInvoiceIntegrationService` is now a 61-line orchestrator. Invoice DTO
  preparation and purchase quantity updates are isolated behind dedicated services,
  and the preparation result is a readonly DTO instead of an associative array.
- GRN and return workflows depend directly on purchase-order quantity behavior rather
  than the broad purchase-order facade.
- Purchase order calculation uses one line aggregation path for totals and header
  adjustment bases.
- All Purchase resources extend one lightweight base resource. Resource container
  lookups and quantity calculations were removed.
- Purchase order quantity aggregates are loaded by queries/services and protected by
  API coverage.
- Purchase requests share scope rules and nullable scalar conversion through a small
  Purchase request base. Existing HTTP validation rules remain unchanged.
- `PurchaseDebitNoteData` was renamed to `CreatePurchaseDebitNoteData`.
- `ListPurchaseOrderRequest` was renamed to `ListPurchaseDocumentRequest` to match its
  actual use across orders, GRNs, returns, and debit notes.

### Frontend And API

- `purchaseApi.ts` was reduced from 219 lines to a five-line compatibility barrel.
  Workflow API modules now contain orders, GRNs, returns, and invoices/payment
  preparation.
- Shared date and decimal fallback helpers replaced repeated form-local functions.
- Purchase order and purchase return capability rules are centralized and preserve
  the prior action conditions.
- The purchase-order line editor moved from 287 lines to a 140-line coordinator plus
  a focused 101-line form and a 78-line model/helper module.
- The header-adjustment editor moved from 259 lines to a 127-line coordinator plus a
  focused 113-line form and a 42-line model/helper module.
- The purchase invoice line table was extracted, and backend source-indexed validation
  errors are now mapped to the correct source.
- Removed unused document-tab components, linked-document tab, invoice lookup helper,
  unused order-form import, and unused invoice normalization collection.

### Tests Added

- Partial receipt -> partial invoice -> supplier payment preparation.
- Sequential duplicate invoice quantity prevention.
- Supplier advance payment -> later supplier invoice allocation.
- Purchase order resource quantity aggregate output.

### Verification

- Purchase module: 23 tests, 99 assertions.
- Invoice module: 3 tests, 16 assertions.
- Payment module: 23 tests, 99 assertions.
- Full repository: 247 tests, 1,667 assertions.
- PHP syntax validation: passed for all Purchase PHP files.
- Purchase routes: resolved successfully.
- TypeScript typecheck: passed.
- Production frontend build: passed.
- Laravel Pint: passed.
- `git diff --check`: passed.

## Remaining Risks

1. `PurchaseInvoiceDtoFactory` remains large at 449 lines. It has one responsibility,
   but its two source-specific mapping paths are still substantial. Further splitting
   was avoided because it would add strategy-style abstractions without reducing
   current workflow complexity.
2. `PurchaseReturnService` and `GoodsReceiptNoteService` remain large but cohesive
   transactional orchestrators. Splitting them now would mostly move transaction
   steps into smaller wrappers.
3. Explicit 3-way matching is not implemented. There are no tolerance rules, match
   statuses, or matching UI; current behavior only enforces source quantity limits.
4. Sequential double invoicing is prevented, but invoice source allocation does not
   visibly lock the source quantity rows. A concurrent race remains possible.
5. Purchase document numbering still uses tenant row count plus one. Concurrent
   generation can collide, and deleted rows can produce reused candidates.
6. Organization-scoped creation validation permits tenant-global references and, when
   no organization is supplied, can also accept organization-specific references.
   Controller reads use exact organization scoping. This pre-existing policy should
   be clarified before changing validation behavior.
7. API result types for invoice creation/preview, payment preparation, and inventory
   adjustment remain broad `Record<string, unknown>` contracts.
8. There is no frontend component-test infrastructure; frontend verification is
   currently typecheck and production build coverage.
