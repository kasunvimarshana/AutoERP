# Vehicle Rental invoice settlement handoff

Date: 2026-07-12

## Evidence

Vehicle Rental creates authoritative Invoice-module documents from approved calculations:

- outbound rental invoices for lessee receivables;
- inbound rental invoices for vehicle-owner payables.

The Invoice detail workspace already owned approval, posting, source traceability, and balances, while the Payment module already supported atomic creation of payment lines with pending specific-invoice allocations. The rental handoff stopped at the Invoice detail page, so users had no guided path from a posted lessee invoice to a receipt or from a posted owner payable to an owner payment.

## Correction

### Invoice-owned handoff

- Posted or partially paid rental invoices with a positive balance now expose a Payment-owned settlement action when the user has `payments.create` permission.
- Outbound rental invoices show `Receive lessee payment`.
- Inbound rental invoices show `Pay vehicle owner`.
- Draft, approved, paid, cancelled, void, zero-balance, and unauthorized documents do not expose the settlement action.

### Payment-owned settlement entry

- `/payments/create?invoice_id={id}` loads the authoritative invoice before allowing settlement entry.
- The invoice direction determines the immutable settlement mapping:
  - outbound invoice -> inbound `customer_receipt`;
  - inbound invoice -> outbound `supplier_payment`.
- Invoice party and currency are displayed in human-readable form and locked to the source invoice.
- The invoice remaining balance pre-fills the payment line while still allowing a valid partial settlement.
- Payment total cannot exceed the current invoice balance.
- Payment creation includes one `specific_invoice` allocation in the same request, preserving the existing Payment-module transaction, validation, snapshot, concurrency, and pending-allocation lifecycle.

## Ownership

- Vehicle Rental continues to own calculation sources and the customer/owner commercial meaning.
- Invoice continues to own the financial document and balance.
- Payment continues to own receipt/payment instruments, allocations, posting, settlement, refunds, reversals, and cheque lifecycle.
- No rental-specific duplicate payment or allocation service was introduced.

## Verification

- Added behavioral frontend coverage for both rental settlement directions and the exact atomic payment/allocation payload.
- Extended the Invoice lifecycle handoff contract to cover the Payment-owned route, permission, currency, and allocation contract.
- Re-read the remote Invoice and Payment source after each write.
- No schema, backend settlement rule, accounting posting, calculation, tax, or public API change was made.
- Full TypeScript, ESLint, Vite, frontend, SQLite, and MySQL/MariaDB gates must be rerun from the latest `worktree-0.0.8` head before release approval.
