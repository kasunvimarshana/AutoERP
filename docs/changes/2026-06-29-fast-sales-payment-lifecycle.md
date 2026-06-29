# Fast Sales Payment Lifecycle

Fast Sales customer receipts now use the Payment module as the authoritative owner of receipt creation, document approval, Finance posting, and invoice-allocation realization.

## Changed

- Removed the client-selected Finance deposit account from Fast Sales context and UI.
- Explicitly reject legacy `destination_account_id` fields instead of silently accepting them.
- Create receipt documents as Payment drafts, then submit, approve, and post through Payment-owned lifecycle services.
- Removed direct receipt journal construction from Sales.
- Updated payment-method lookups to expose `requires_instrument_details`.
- Split the oversized Fast Sales service into responsibility-focused traits without changing the public service contract.
- Added regression coverage preventing Fast Sales from reintroducing direct Finance-account selection or obsolete Payment status usage.

## Verification

- PHP syntax checks passed for the Fast Sales orchestrator, all concern traits, request, and regression test.
- TypeScript syntax transpilation passed for the Fast Sales page.
- Static Payment-boundary assertions cover the backend lifecycle and frontend account-selection boundary.
- Full Laravel, database, PHPUnit, TypeScript semantic, ESLint, Vitest, and Vite release gates remain environment-dependent and must run before a production release.
