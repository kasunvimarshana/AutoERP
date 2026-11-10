# Direct Customer default for Selling

**Date:** 2026-11-10

The Create Selling form now loads a tenant-wide Direct Customer record as its selected customer. The customer can be replaced through the existing searchable customer control. Selling exposes a sales-create-authorized lookup so a cashier can search active customers without needing customer master-data view permission.

The Customer module owns idempotent Direct Customer creation. A sales-create-authorized POST ensures the record on first form load, using a stable code and a tenant row lock so concurrent requests cannot create duplicates. The record has an ordinary customer relationship, no fake contact details, and no credit allowance. Existing Walk-in Customer records and historical sales remain untouched. If the reserved Direct Customer record has been changed or deactivated, the request fails explicitly instead of selecting a different customer silently.

The existing sale posting flow continues to use the selected customer for both the sale and its invoice. Fixed the shared lookup component so typing to replace a selected value preserves the new search text.

Verification: Selling workflow PHPUnit suite passed (7 tests, 92 assertions); Selling page and shared lookup Vitest suites passed (8 tests); TypeScript typecheck, focused ESLint, Pint, and `git diff --check` passed.
