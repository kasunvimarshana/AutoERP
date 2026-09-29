# Rental recorded OT and night-out billing — 2026-09-12

Baseline: `d9695b897f2be2707b6334bdb17dffba3679f2be` on `worktree-0.0.8`.

Implement a complete charge-to-Invoice workflow for finalized chart normal/double/triple OT and night-outs on both commercial sides. Price the exact agreement revision assigned to the vehicle use. Require explicit acceptance of the named recorded-minutes/nights policy. Multiply integer minutes by the agreed category rate before dividing by minutes per hour; never add another category multiplier or infer qualification from elapsed chart time. Unknown and zero remain distinct, overflow fails, and no client monetary input controls the result.

Show a read-only server quote before creation. Persist component, quantity, rate, denominator, policy, chart/agreement revisions and amount; create the Invoice draft atomically. Add scoped history, unchanged reissue and audited void. Invoice owns Tax/Finance preparation and its source-consumption guard. Customer consumption is independent of owner consumption. Both sides must release their invoices and void their assessments before physical chart reversal.

Use vehicle-first locking shared with physical operations, then agreement/chart/charge locks and version checks. Reject duplicate non-voided chart/component consumption under the source mutex. Source identity and calculation remain immutable. Released Invoice states are terminal in the existing Invoice state machine; their release allows reissue/void, not resurrection of the old document.

Relationship review: two explicit side-specific usage-charge tables retain mandatory tenant-composite agreement, chart, organization and actor references. The agreement FK freezes and validates the commercial owner; the chart FK identifies consumed physical evidence. Avoid nullable customer/owner XOR fields, unenforced polymorphic agreement IDs and inverse Invoice pointers. Retained void history requires a non-unique chart/component index alongside the locked active-consumption check. Rename the existing fresh shared charge base class to `RentalCharge`; base and usage charges share its immutable calculation contract. Extract current base billing's document preparation/release checks to `RentalChargeDocuments`, preserving separate pricing responsibilities without duplicating Tax or Invoice behavior. No removed Rental code/history was used.

Add authenticated API routes and guided controls in the Running Chart register and vehicle chart list. Relationships derive from selected records; no typed foreign keys. UI shows quantity/rate/amount and missing-value reasons, requires policy acceptance, links Invoice documents and permits correction controls only for released documents. Add the detailed `usage-billing.md` contract, knowledge base section 39 and scoped completed TODO entries. Do not close unrelated mileage/deposit/settlement requirements.

Verification:

- Full PHP/SQLite suite: **777 tests, 8,529 assertions**.
- Full frontend suite: **86 files, 320 tests**.
- Pint, ESLint, TypeScript, production Vite build and whitespace checks passed.
- Coverage includes all four components, independent sides, exact minute arithmetic, read-only quotes, zero/unknown/overflow, duplicate/stale rejection, authenticated permissions/entitlements/tenant boundaries, reissue/void/history, reciprocal physical-reversal blocking and rollback on missing Tax configuration.
- Both sides execute Invoice approval, Finance posting, reversal and reissue using test-only semantic account mappings.
- Isolated fresh SQLite migration/seeding passed. Upgrading the verified base-billing schema ran exactly the two new usage-charge migrations. Fresh/upgraded column and foreign-key structures matched across 225 tables including SQLite metadata. Rolling back those two migrations and reapplying both passed.

Real InnoDB contention, production-data upgrade and human UAT were not executed. The prior free MariaDB initialization succeeded but its socket startup was denied by the environment; that is not a successful database test. No paid tools, GitHub Actions or deployment. No new legitimate protected-backup password evidence was found, and the password did not block this implementation.
