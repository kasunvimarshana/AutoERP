# Show the sales bill after finalizing payment

## Request

After a Selling payment is finalized, show the bill preview with direct actions to print it or return to Selling instead of opening the Finance payment detail screen.

## Changes

- Added a Selling payment completion page that loads the posted customer receipt and its linked sales invoice, then previews the invoice PDF using the existing signed print link.
- Added Print bill, Return to Selling, Refresh preview, and a secondary View payment receipt link. Print obtains a fresh signed link so an expired preview link does not affect printing.
- Redirected only Selling payment finalization to the completion page. Other payment creation still opens the Finance payment detail screen.
- Scoped the new route to the Selling, Invoice, and Payment modules and the existing payment view permission. API permissions continue to govern invoice and print access.

## Verification

- TypeScript typecheck, targeted ESLint, and `git diff --check` passed.
- Automated tests were not run.
