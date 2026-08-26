# Invoice signed print route path fix

Date: 2026-07-21

## Problem

Invoice print actions generated signed public URLs under `/public/invoices/...`. That exposed an infrastructure-oriented path in the user-facing URL and caused production print tabs to land on a route the app did not handle correctly.

## Change

- changed the signed invoice print route from `/public/invoices/{invoice}/print/{tenant}` to `/signed/invoices/{invoice}/print/{tenant}`;
- changed the signed invoice PDF route from `/public/invoices/{invoice}/pdf/{tenant}` to `/signed/invoices/{invoice}/pdf/{tenant}`;
- added a regression assertion in the invoice print test so generated signed links must use the neutral `/signed/invoices/` path.

## Verification

- `php artisan test --filter=InvoicePrintServiceTest`
- `php artisan route:list --name=invoices.public.print`
- `php artisan route:list --name=invoices.public.pdf`

## Scope

This change affects only the invoice signed print and PDF route paths plus their regression coverage.
