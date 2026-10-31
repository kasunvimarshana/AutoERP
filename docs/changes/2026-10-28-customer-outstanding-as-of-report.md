# Customer outstanding as-at report

## Why

The customer outstanding screen previously used today's `invoice_balances.remaining_amount` even when an earlier report date was selected. This made historical balances and aging misleading.

## Changes

- Reconstruct each outbound customer invoice's balance as at the selected date from its original amount, payment allocations, sale-return credits, and dated reversal journals.
- Fail with a validation error for older records whose reversal or credit date cannot be verified instead of presenting an invented historical balance.
- Scope the report and selectable locations to the current user's accessible organization units. Keep customer and invoice detail requests within the same scope.
- Show location groups, customer totals, invoice age and due days, location totals, and currency totals. CSV uses the same calculation and includes invoice, customer, location, and grand-total rows.
- Replace the invoice date range with a single as-at date and remove the inferred customer credit-profile filter. Current invoice status is not displayed as a historical status.

## Verification

- Focused Finance tests cover dated payments, sale-return credits, later payment reversal, incomplete reversal history, and inaccessible location selection.
- TypeScript type checking and targeted PHP formatting checks completed.
