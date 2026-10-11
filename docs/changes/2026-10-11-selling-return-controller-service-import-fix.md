# Selling return controller DI regression — 2026-10-11

## Reviewed source and root cause
- The existing POST `api/v1/selling/sales/{sale}/returns` route maps to `SaleController::storeReturn`, which type-hints `SaleReturnPostingService` in the controller's dependency injection signature.
- The service is defined as `Modules\\Selling\\Services\\SaleReturnPostingService`; the controller previously imported only `SalePostingService` from that namespace. PHP therefore resolves its unqualified `SaleReturnPostingService` type to the wrong `Modules\\Selling\\Http\\Controllers\\SaleReturnPostingService` class.
- The earlier `SellingWorkflowTest` directly constructed the correct service, and therefore did not detect the route's controller dependency mismatch. The missing import predates this PR and is a concrete existing functionality bug, not a justification for new backwards-compatibility logic.

## Fix and validation
- Add the missing explicit `use Modules\\Selling\\Services\\SaleReturnPostingService;` in the Selling controller; no endpoint contract, route, finance ledger, data schema, runtime side effect or cross-module responsibility changed.
- Add a focused `SellingWorkflowTest` reflection regression asserting that the `storeReturn` service argument resolves to the actual Selling posting service class, not the controller namespace.
- Inspected the exact controller, route, request, service, existing test and recent dated change records before editing.
- The reflection regression test is authored but not executed inside the application because the runtime has no complete dependency-backed checkout.
- GitHub Actions jobs continue to fail at zero steps (issue #124), so this change is staged on PR #126 and inherited into integrated PR #127, not production-promoted.

**Release status:** not verified; full backend/DB/finance checks and PR #127 review gates still required.
