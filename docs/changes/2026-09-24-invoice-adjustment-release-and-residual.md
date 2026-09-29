# Invoice adjustment release and residual reconciliation — 2026-09-24

Baseline: `250888bf1bc6b39aaf0aafbe1ea7275ca3068dda` on `worktree-0.0.8`. Independently verified GitHub's reference and fetched both previously published mileage/deposit commits. The restored scratch workspace held the exact already-published mileage tree; switched through that matching commit before advancing to the current branch without discarding unique work.

Reviewing the financial foundation for further Rental adjustments exposed two Invoice-owned defects. A reversed invoice released source quantity but still consumed adjustment capacity, preventing valid reissue. Proportional header adjustments rounded each installment independently and could strand a six-decimal remainder. Added failing integration tests before fixing either defect.

Exclude reversed, cancelled and voided invoices consistently from surviving adjustment allocations. Calculate identified proportional adjustments from cumulative surviving invoiced basis, quantize after multiplication/division, then subtract exact surviving allocated amounts. Include earlier basis only where the same adjustment identity actually participated. Sum persisted decimal strings through DecimalMath rather than database floating aggregates. Reject excess basis and negative catch-up from incompatible historical manual allocations. Preserve manual/first/last allocation choices and immutable source history.

Relationship review: existing InvoiceSource and InvoiceAdjustmentAllocation references provide the necessary history. No new table, inverse reference or Rental dependency is justified. This corrects the responsible financial owner instead of compensating in Rental. Source owners still must hold their aggregate mutex for the entire creation transaction; this change does not claim that querying an empty allocation set provides concurrency exclusion.

Knowledge base section 42 explains the rule and limits. Reconcile stale TODO boxes for delivered base-rent/mileage/OT/night-out invariants and canonical handoff, explicitly retaining the distinction between duplicate prevention and response replay. Do not mark broader driver, downtime, surcharge or production acceptance requirements complete without delivery evidence.

Verification:

- Before fix: one-unit adjustment over three installments stranded 0.000001; a real posted/reversed invoice could not be reissued because adjustment capacity remained consumed.
- After fix: targeted Invoice engine suite passes, including exact final residual, cancellation/replacement reconciliation and actual Finance-backed posting/reversal/reissue.
- Full backend SQLite suite: **786 tests, 8,679 assertions**.
- Full frontend suite: **88 files, 325 tests**.
- Pint, ESLint, TypeScript, production build and whitespace checks passed.
- No schema change. The prior published deposit/mileage fresh-schema verification remains applicable; this commit changes only allocation arithmetic, read filtering, tests and documentation.

No paid tools, GitHub Actions, force push or deployment. No old Rental code was read or restored. This verification does not establish real InnoDB contention behavior, production-data migration or human UAT.
