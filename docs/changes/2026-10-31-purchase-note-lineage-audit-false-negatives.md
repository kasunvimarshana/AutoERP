# Purchase debit-note historical lineage audit: scope/supplier false negatives — 2026-10-31

## Root cause, confirmed in source

The existing SELECT-only Purchase reconciliation query #5 in `docs/audits/purchase-debit-note-currency-reconciliation.sql` selected a Return→GRN→Order currency candidate only when the note/return/order currencies were unknown or different. A historical Purchase debit note and its linked Purchase return with **matching stored currencies but different organization units or suppliers** were therefore omitted from that lineage review. The application service `PurchaseDebitNoteService::assertSourceContract()` already rejects these mismatches for **new** notes; the gap was in the investigation aid for legacy records, not a new financial posting bug.

The migration schemas establish `tenant_id`, `organization_unit_id`, `supplier_type`, `supplier_id` on Purchase debit notes and returns. Existing source joins remain explicitly tenant-scoped. No supplier, currency, FX or accounting policy has been inferred.

## Minimal fix

- Extend the existing fifth SELECT with note/return organization and supplier IDs/types, so reviewers can see conflicting source provenance.
- Include **NULL-asymmetric organizations**, differing known organizations, missing or differing supplier IDs, and non-supplier source party types as investigation triggers, even where the currency IDs match.
- Retain the first four existing SELECT reports and the same tenant-bound source joins. No `UPDATE`, `INSERT`, automated backfill, database migration, Finance journal or invoice balance adjustment.
- Extend the existing dependency-free `docs/audits/test_purchase_debit_note_currency_reconciliation.py` fixture with historical scope/supplier identity columns and one regression method covering the three previously unreported cases.

## Execution evidence and limitations

- Reproduced the original query's false negative with **three** matching-currency historical anomalies in an isolated in-memory SQLite database: differing organization, NULL-asymmetric organization and differing supplier. Original predicate returned **zero** review rows; corrected predicate returned **three**.
- Independently executed the corrected fifth SELECT's selected columns, joins and all predicates in a disposable in-memory SQLite fixture with one existing unresolved-currency row and those three scope/supplier anomalies: **four expected review rows returned**, identities validated.
- The repository's complete six-test Python audit suite and a populated MySQL historical dataset were **not executed** in this environment. The added Python regression is committed for repeatability, but should not be reported as passed solely because its SQL predicate was smoke-tested.
- The user's separate Windows transcript preceding this audit change records **958 Laravel tests / 10,634 assertions**, **468 Vitest tests / 129 files**, and successful lint/typecheck/build on a clean up-to-date candidate branch. It did **not** run this subsequent audit change and did not include a `git rev-parse HEAD` attestation.

## Open acceptance

This change only closes a *read-only investigation false negative*. It does not establish the correct historical monetary currency for a Purchase document, execute real-data reconciliation, balance Finance/GL, validate other GRN/Order scope lineage, resolve issues #124/#128 or finish Rental TODO §R. The SELECT may be used only with authorized isolated restored data and accountable Finance review. Production database access, credit/FX/GL changes, and automatic remediation are expressly excluded.
