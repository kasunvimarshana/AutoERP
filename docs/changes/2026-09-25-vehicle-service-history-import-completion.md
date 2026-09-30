# Vehicle service history import completion

Date: 2026-09-25

## Recovery authorized

The user confirmed that the lost local development data was not required and authorized rebuilding the local database and completing the previously approved vehicle-history import.

The local `laravel` database was rebuilt from the latest identified current-system baseline, `C:\Users\Sadheera\Downloads\laravel (9).sql`, then supplemented with the documented compatible master-data artifact at `storage/app/private/imports/2026-09-01-source-master-data.sql`.

Restored master-data counts before history import:

- Customers: 1,339
- Vehicles: 1,891
- Current vehicle ownerships: 1,748
- Items: 675
- Live vehicle-service jobs from the baseline: 3

Current pending migrations were then applied successfully, including the six immutable imported-history tables.

## Approved history import

The old-system dump was parsed as untrusted data and was never executed. Its verified SHA-256 was `3f182446d68ddd3ed3ec29b8bf2bdc56f3140c7eefbde87cb29d723aae3d4d31`.

The final dry run reported no blocking issues. The atomic apply imported:

- Imported jobs: 18
- Job lines: 59
- Status events: 62
- Employee assignments: 36
- Financial document snapshots: 11 (8 invoice and 3 payment snapshots)

All 18 job vehicles matched deterministically by normalized registration number. Sixteen supervisor references and 30 workforce assignments that had no current HR match were retained using original source snapshots. Seven old inventory movement references remain informational only.

The import did not create or modify live inventory movements, invoices, payments, finance entries, or operational service jobs. Reconciled live counts remained unchanged at 3 service jobs, 3 inventory movements, and 3 finance journal entries; invoice and payment counts remained zero.

## Verification

- A second apply of the same source returned `already_imported` for batch 1 and created no duplicates.
- Duplicate imported source identities: 0.
- Orphan imported lines, assignments, and status events: 0.
- The unified history report returned 21 records: 18 imported and 3 current.
- Imported rows return Job Type `Full Service`, source `Old AutoERP`, and no live-job detail link.
- The parser regression test passed with 6 assertions.
- PHP syntax and Laravel Pint checks passed.
- Frontend TypeScript checking passed.

The command's apply result now explicitly reports `mode: apply`; dry runs continue to report `mode: dry_run`.
