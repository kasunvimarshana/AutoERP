# Focus Selling invoice payment entry

## Request

Make the payment page reached from a posted Selling sale as clear and focused as the Vehicle Service payment page, while preserving the existing Finance receipt lifecycle.

## Changes

- Selling's Receive payment link requests a Selling-specific view of the existing invoice settlement page.
- The Selling view shows the customer, invoice total, paid and outstanding amounts, a compact payment form, live payment and remaining balances, and invoice print and WhatsApp actions.
- A credit choice appears only for sales recorded with a due date. It confirms the invoice is already on credit and creates no payment record.
- Customer receipts still use the Payment module's existing create and allocation API, idempotency handling, and draft-to-post lifecycle. Other callers of the general payment entry page keep its original interface.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
