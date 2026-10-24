# Keep Selling payment completion on the payment page

## Request

Reduce steps after finalizing a Selling payment by showing the bill and its actions on the current payment page.

## Changes

- The Selling payment page now keeps the posted receipt in local state after finalization and replaces the form with a completion view on the same URL.
- The completion view shows the receipt amount and reference, refreshes the invoice balance, previews the bill, and offers Print bill, Share via WhatsApp, and Return to Selling.
- Print and WhatsApp reuse the page's existing signed print and verified-number share flows. The normal non-Selling payment redirect remains unchanged.
- Removed the separate Selling completion route and page, which are no longer part of the flow.

## Verification

- TypeScript typecheck, targeted ESLint, and `git diff --check` passed.
- Automated tests were not run.
