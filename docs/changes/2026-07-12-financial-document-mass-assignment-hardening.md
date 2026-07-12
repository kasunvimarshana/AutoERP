# Financial document mass-assignment hardening

Date: 2026-07-12

## Problem

The Invoice, Payment, and TaxTransaction financial models overrode the Core deny-by-default write policy with `guarded = ['id']`. That left server-owned financial fields broadly assignable if a future request, import, maintenance command, or integration passed an unreviewed attribute array to `create()` or `fill()`.

## Root cause

The feature models retained permissive Eloquent defaults after Core established a deny-by-default mass-assignment foundation. Their owner services already constructed authoritative financial payloads, but the model boundary did not enforce that ownership.

## Correction

- Removed the permissive guard overrides from Invoice, Payment, and TaxTransaction so they inherit Core's total guard.
- Updated InvoiceCreationService, PaymentCreationService, and TaxSnapshotService to instantiate their owned model, explicitly `forceFill()` the authoritative server-generated payload, and save it.
- Updated the two direct Payment fixture builders to seed guarded Payment records explicitly without weakening the production model.
- Added module-owned boundary tests for Invoice, Payment, and TaxTransaction.
- Left child models and unrelated financial lifecycle logic unchanged.

## Scope

This change does not alter totals, tax calculations, invoice or payment states, allocation behavior, journal posting, snapshots, database schema, APIs, or UI behavior. It only narrows the Eloquent write surface and preserves the same owner-service payloads.

## Verification

- PHP syntax validation passed for every modified production file and new or adjusted test file.
- Re-fetched the modified owner services and confirmed explicit controlled writes are present.
- Confirmed Invoice, Payment, and TaxTransaction inherit `CoreModel::$guarded = ['*']`.
- Repository search confirmed each hardened financial record has one production creation owner.
- The complete Laravel and frontend suites must be rerun in the normal project checkout before release.
