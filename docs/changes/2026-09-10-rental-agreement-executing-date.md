# Separate agreement executing-date capture — 2026-09-10

Baseline: `b7111adcba2fb253fafa60d3279b39578cf742b1`, verified latest `worktree-0.0.8`.

Reinspect `1.mp4` at 03:15: the customer agreement has distinct Agreement Date, Agreement Executing Date, Agreement Starting Date and Agreement Ending Date labels. At 16:45 the owner-side Payment Payable Voucher also exposes the agreement executing date separately. These screens support capturing a distinct fact; they do not prove mandatory entry, cross-date ordering, automatic activation or billing effects.

Add nullable `executing_on` to both fresh agreement aggregates, strict calendar validation, named shared date format, API/review/draft input and immutable history. Omitted/blank input remains unknown. Version-checked draft updates can record or clear the date while retaining prior snapshots; active agreement terms remain immutable. Earlier history without the field returns null and is never rewritten or inferred from another date.

Update the knowledge base, agreement contract and TODO. Mark the date-capture tasks complete while leaving commercial date effects unproven. Reconcile the obsolete vehicle-first atomic-write TODO with the existing implementation; real-engine contention remains open.

Relationship review: no new or changed relationships, keys, inverse collections or module dependencies. Rental owns this agreement fact. Under the repository's fresh-baseline rule, each original agreement creation migration receives one explicit nullable date column. This is not an automatic upgrade of an already migrated database. An installed earlier revision requires an inspected deployment-specific schema upgrade, without dropping history or inventing dates. No production database was accessed.

Verification:

- Full PHP 8.3/SQLite suite: **715 tests, 8,106 assertions passed**, including fresh schema and migration architecture checks.
- Frontend: **81 files, 301 tests passed**.
- PHP Pint, ESLint, TypeScript, production build and whitespace checks passed.
- New tests cover both commercial sides, independent date output, null/default/clearing, preserved revisions, invalid calendar input, active-edit rejection, authenticated request forwarding and UI input/submission.

Free local tools only, no GitHub Actions or deployment. No removed Rental code reused. Full audiovisual review, commercial versions/calculations/consumption, driver identity, financial handoffs and production/MySQL/browser acceptance remain unfinished; this is not a complete-module release.
