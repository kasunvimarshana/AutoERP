# Vehicle Rental removal regression cleanup

Date: 2026-08-27

## Purpose

Close stale test and frontend references exposed by local verification after the Vehicle Rental runtime was removed.

## Changes

- remove the obsolete Vehicle Rental report value formatter unit test together with its retired implementation;
- remove Vehicle Rental from the tenant plan editor module group by restoring the known-good pre-reactivation editor blob;
- remove the invoice legal-print source-code assertion that read the deleted Rental financial document factory;
- keep historical Rental and Vehicle Finance invoices read-only for source-dependent lifecycle actions while deriving the user-facing retired-source label from `InvoiceType` as the single source of truth.

## Scope boundary

This cleanup does not modify unrelated Vehicle Service migrations, Vehicle optional-odometer behavior, or Vehicle Service inventory-line version handling. Those failures were present outside the Vehicle Rental removal diff and remain owned by their respective modules.

## Verification expectation

Re-run the Laravel suite, TypeScript typecheck, frontend tests, lint, and production build from a normal checkout. The removal-specific stale-reference failures addressed here should no longer occur.
