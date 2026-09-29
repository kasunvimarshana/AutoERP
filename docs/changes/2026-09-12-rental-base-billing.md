# Rental base-rent billing and correction — 2026-09-12

Baseline: `562ac72116e9f4d68cf65a22be4d3a5a401f113e`, verified on `worktree-0.0.8` before implementation and again before publication.

Implement a fresh base-rent financial workflow for both sides: explicitly apply the actual-calendar policy, preserve the calculation, create a source-backed Invoice draft atomically, review linked documents, reissue released invoices from the unchanged charge, and void an incorrect charge with revision/actor/reason/time before replacement. Hold the agreement mutex while checking overlapping non-voided periods and creating or correcting charges. Reject draft agreements, stale revisions, unknown/zero billable amounts, invalid dates/exchange inputs and monetary overflow. A failed Tax/Invoice preparation rolls back the charge.

Use separate customer/owner base-charge tables with mandatory tenant-composite agreement and actor references. This avoids mutually exclusive nullable agreement relationships and unenforced polymorphic IDs. A small shared model holds identical calculation/void invariants. Keep the existing Invoice-owned source link instead of duplicating an invoice foreign key in Rental. Each base-charge source has one indivisible quantity. A non-unique period index supports corrected records after void; the locked overlap check enforces active uniqueness. Calculation history is retained; void is the only permitted charge update. No old removed Rental code or history was used.

Invoice owns a shared taxed-source factory and scoped source-document lookup. Manual invoices also use this factory, eliminating duplicate tax mapping and correcting their posting treatment of inclusive tax and withholding. Tax owns component serialization. The posting base equals payment plus withholding minus ordinary tax; withholding is not ordinary input/output tax. Rental uses existing Finance role/profile enums and Sales/Purchase Invoice types; it does not revive the retired historical Rental Invoice type or seed statutory rates/account numbers.

Add billing permissions, Invoice entitlement enforcement, authenticated APIs and an agreement-review billing UI. The UI collects period/document inputs and explicit policy acceptance, displays human-readable charge/document history and offers governed reissue/void actions. API values remain authoritative. Add shared Invoice status enums for the new controls.

A full frontend run exposed the shared dialog's delayed-focus race. Its scheduled callback now preserves focus already inside the dialog, and cleanup cancels the frame. Two focused regression tests failed against the prior hook and pass with the correction. Fix the owning hook rather than adding Rental-specific timing workarounds.

Documentation: update knowledge base section 38, add the full `base-billing.md` contract and close the base-only TODO items. Usage-based components, deposits and broader acceptance are not falsely marked implemented by base billing.

Verification:

- Full PHP/SQLite suite: **767 tests, 8,383 assertions**.
- Full frontend suite: **85 files, 314 tests**.
- Pint, ESLint, TypeScript, production Vite build, whitespace and local documentation links passed.
- New coverage includes customer/owner independence, period overlaps and adjacency, immutable values, stale revisions, cancellation/reissue, audited void and same-period replacement, tenant/permission/entitlement enforcement, zero/overflow rejection and atomic rollback on missing tax configuration.
- Synthetic inclusive-tax/withholding coverage checks exact invoice/Finance-plan reconciliation for Rental and manual invoices.
- Both sides execute governed Invoice approval, Finance posting, reversal and unchanged-charge reissue using explicit test-only Finance mappings.
- Fresh migration/seeding passed on an isolated SQLite database. A second database was built from all 224 migration/upgrade files at the baseline commit; applying the current code ran exactly the two new charge migrations. Fresh and upgraded column/foreign-key structures matched across 222 tables.

Real InnoDB contention, a production-data upgrade and human/browser UAT are not established by these checks. The protected backup has no newly verified password; prior exact-candidate attempts remain unsuccessful and did not block this implementation. No paid tooling, GitHub Actions or deployment.
