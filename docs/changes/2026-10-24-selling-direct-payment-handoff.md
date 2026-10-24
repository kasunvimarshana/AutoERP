# Open payment immediately after posting a sale

## Request

Make Receive payment prominent on the sale detail page and take the user directly to the Selling payment page after Post sale and invoice.

## Changes

- After a sale posts, the create screen uses the linked invoice returned by the Selling API to open the Selling payment page directly when that invoice has an outstanding balance and the user has the required payment, method, invoice, and sale permissions.
- If payment entry is unavailable or the invoice has no outstanding balance, the existing sale detail destination remains available.
- Moved Receive payment from a small invoice-row text link to a prominent action at the top of the sale detail page. The action uses the same Selling payment URL as the post-sale handoff.
- No sale, invoice, or payment relationships were changed; the existing linked invoice remains the source of the handoff.

## Verification

- TypeScript typecheck and `git diff --check` passed. Targeted ESLint completed with one pre-existing React effect warning in `SellingWorkspacePage.tsx` and no errors.
- Automated tests were not run.
