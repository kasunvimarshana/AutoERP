# Selling nonempty line invariants at posting service boundaries (2026-10-11)

## Evidence and root cause

The Selling HTTP request validators `StoreSaleRequest` and `StoreSaleReturnRequest` require `lines` to be a nonempty array. The directly callable `SalePostingService::create` and `SaleReturnPostingService::create` did not enforce that invariant themselves.

Consequently, internal callers could submit an empty array, bypassing the public request validation. In the return service in particular, the empty foreach path progressed to a zero-credit return document, despite having no returned stock evidence. This is a business-integrity violation independently of how an invoice creation service might handle empty sale lines.

## Implementation

- Validate a nonempty lines array at the start of both owning posting services, before starting a database transaction or acquiring an idempotency key. Invalid input fails predictably with `InvalidArgumentException`.
- Extend the existing `SellingWorkflowTest` fixture to assert that direct service calls with empty lines cannot create a sale/invoice or return/credit and do not change stock.
- Preserve all existing valid selling and return transactions. No new configuration, duplicate validation layer in another module, guessed financial rule, or compatibility workaround.

## Verification state

The changed service source and test assertions were reviewed from the exact integration candidate. This does **not** count as executed testing: GitHub Actions currently terminates at zero job steps (issue #124), and the connected environment cannot run full AutoERP dependencies. Keep production merge blocked until the complete test matrix is green.
