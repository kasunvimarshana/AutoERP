# Imported vehicle service history foundation

Date: 2026-09-24

## Why

The uploaded old AutoERP SQL dump contains vehicle service jobs that must remain visible as historical evidence without recreating live operational jobs or posting inventory, invoice, payment, or finance transactions. Its job, invoice, and payment numbers also collide with documents already present in the current system.

## Changes

- Added Vehicle Service-owned immutable import batch, job, line, status event, employee assignment, and financial-document snapshot tables.
- Added a strict allowlist SQL dump reader. The dump is parsed as untrusted data and is never executed.
- Added `vehicle-service:import-history`, which is dry-run-only unless `--apply` is supplied explicitly.
- Added deterministic vehicle matching by normalized registration first and vehicle code second. Conflicting or ambiguous identifiers block the import.
- Added source identity and payload hashes plus database unique constraints so repeated imports cannot duplicate history and changed source records cannot silently replace imported history.
- Kept old inventory movement, invoice, and payment references informational; the importer performs no ledger writes.
- Unified the Vehicle Service History report across current jobs and imported immutable history.
- Added visible Job Type and source badges to each history card. Imported job numbers are plain historical references and do not link to current-system job screens.
- Added parser regression coverage for allowlisted data, SQL comments/statements, escaped quotes, commas, and quoted whitespace.

## Dry-run evidence

Source file SHA-256: `3f182446d68ddd3ed3ec29b8bf2bdc56f3140c7eefbde87cb29d723aae3d4d31`

- Jobs: 18
- Job lines: 59
- Inspections: 11
- Status events: 62
- Employee assignments: 36
- Invoice summaries: 8
- Payment summaries: 3
- Vehicle matches by normalized registration: 18
- Blocking issues: 0
- Existing-number collisions: 18 jobs, 8 invoices, 3 payments
- Unmapped references retained as source snapshots: 16 supervisors and 30 workforce assignments
- Old inventory movement references retained as informational snapshots: 7
- Inventory, invoice, payment, and finance writes: 0

No import was applied. Running the command without `--apply` changed no database data.

## Verification

- PHP syntax checks passed for the command, importer, parser, report service, models, and migrations.
- The focused SQL dump reader test passed with 6 assertions.
- All migrations, including the six new history migrations, completed against the isolated SQLite test database.
- Frontend TypeScript checking passed.
- ESLint reported no errors in the changed frontend files; it retained the page's pre-existing React effect warning.
