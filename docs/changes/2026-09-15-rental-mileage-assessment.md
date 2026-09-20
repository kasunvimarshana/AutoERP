# Rental commercial mileage allowance assessment — 2026-09-15

Baseline: `9ce214406c91499ae3be658166866bd762c04a83` on `worktree-0.0.8`.

Implement the explicitly accepted `commercial_calendar_cycles_v1` policy against finalized chart commercial KM and assigned agreement terms. Daily allowances use civil days; monthly allowances use original-start anniversary cycles with actual-days proration only for a contractually shortened final cycle. Freeze the displayed resolved workspace timezone at the first assessment. Customer charts/replacement vehicles share their agreement allowance; owner agreements remain independent. Do not infer garage billability, whole-distance tariffs or distance splits across boundaries.

Persist zero-cost assessments as well as positive ones so free usage cannot consume its allowance twice. Price cumulative excess before subtracting surviving prior amounts to preserve six-decimal residuals. Reject unknown inputs, overflow, duplicate source use and stale chart/agreement/pool/timezone quotes. Positive assessments create Invoice drafts atomically through the existing Tax/Finance handoff; zero creates no invoice. Keep Invoice supply dates on actual chart coverage while storing allowance-cycle dates for pool identity.

Release financial documents before void. Require later same-cycle mileage assessments to be voided first because they depend on earlier allowance use. Reuse existing immutable source history and physical-chart reversal safeguards. Zero-cost records can be voided without nonexistent invoices and cannot be reissued as zero invoices. Preserve both commercial sides' independence.

Relationship/performance review: reuse current side-specific usage-charge tables, mandatory chart/agreement references and immutable calculation snapshots. Do not add a cached balance, inverse Invoice pointer or growing list of predecessor IDs; retain preceding pool head and exact prior totals. Add two explicit cycle indexes through the established module `UpgradeMigrations` path without rewriting published create migrations. Share current charge document validation/Tax/Finance controls. No removed Rental code/history was used.

Add read-only quote and assessment APIs, an explicit **Assess mileage** view with backend amounts, policy acceptance, zero-cost confirmation and stale-quote recovery. Authenticated tests exposed a missing returned default row version for a new zero assessment; initialize the named version explicitly before returning it. The full migration architecture check required upgrade files in their correct upgrade directory; register that directory through the module provider.

Update knowledge base section 40, the detailed mileage contract and scoped TODO completion. A current Malkey primary-source recheck confirms that external allowance conventions vary; no external numerical defaults were imported. Historical TACGL convention remains distinguished from the selected implementation policy.

Verification:

- Full PHP/SQLite suite: **781 tests, 8,589 assertions**.
- Full frontend suite: **87 files, 322 tests**.
- Pint, ESLint, TypeScript, production Vite build and whitespace checks passed.
- New coverage: shared pools, zero-cost consumption, independent sides, duplicate prevention, exact residual pricing, stale pool quotes, reverse-order correction, short-month anchor recovery, partial final month, daily/cross-cycle boundaries and authenticated positive/zero assessment APIs.
- Fresh migration/seeding, baseline upgrade, rollback/reapply and fresh/upgraded column/FK/index comparison passed on isolated SQLite databases. Exactly two new indexes are applied; no tables or business data are rewritten.

No production-data upgrade, real InnoDB contention or human UAT is claimed. Prior protected-backup password candidates remain unsuccessful; no new evidence justified another candidate attempt. The password did not block development. No paid tools, GitHub Actions or deployment.
