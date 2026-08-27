# Vehicle Rental TACGL deep evidence refresh — 2026-08-27

## Purpose

Refresh `docs/knowledgebase.md` after an independent end-to-end audit of the re-supplied `TACGL(6).zip`, while keeping all four Vehicle Rental videos authoritative for the dedicated Rental workflow and the latest `worktree-0.0.8` branch authoritative for engineering state.

## Source verification

- `TACGL(6).zip` SHA-256: `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`.
- This hash is identical to the previously audited TACGL archive, so the re-upload is the same evidence corpus rather than a second independent dataset.
- The source branch was rechecked immediately before the write at `0b805561c622826d416fbdc4a7e39e83a291fe3f`.
- Existing Vehicle Rental removal and knowledgebase change records were reviewed before editing.

## Knowledge improvements

- Quantified TACGL archive composition and reconfirmed `gl.dbc`/DBF evidence.
- Added exact customer Rental lineage from `LCH2005407` through `INV2005519`, Debtor subledger, `REC2003089` allocation and GL posting.
- Documented that explicit Rental invoice postings were routed primarily through legacy workshop Sales accounts (`SALES: - BREAKDOWN` / `SALES: - TINKERING & PAINTING`), which is business/accounting evidence but not target architecture.
- Added structured-calculation evidence: legacy excess-KM quantity/rate formulas are commonly stored in free text while numeric fields carry final totals, including an observed text/amount mismatch.
- Added non-calendar monthly-cycle evidence (`25 -> 24`, `18 -> 17`).
- Added fixed-30-day proration precedents (`225,000 × 13/30 = 97,500` and an owner-payment arithmetic match `180,000 × 21/30 = 126,000`) while explicitly keeping universal proration policy unresolved.
- Added an exact third-party hire/Outside Work trace (`OWN2003536`) covering hire cost, driver OT/bata, creditor/Pending Jobs effects and customer recovery.
- Quantified regular Rental Payment activity: 25 positive debit rows across 21 `PRB` vouchers to `7048-000 RENTAL PAYMENT`.
- Quantified six normalized duplicate-registration vehicle groups and reconfirmed all 1,076 active `scfveh` rows carry `VEHTYP = 03`, making that legacy type unsuitable as ownership truth.
- Added deleted/replaced Rental transaction evidence and strengthened immutable correction/reversal requirements.
- Reconfirmed 16 PDF exports / 63 pages are predominantly Debtor Outstanding Age Analysis outputs and do not establish new Rental pricing formulas.

## Architecture boundary

No legacy design flaw is adopted. The refresh explicitly rejects:

- duplicate physical Vehicle records for commercial context;
- Rental embedded as workshop Labour/Outside Work;
- Rental revenue classified under workshop Sales accounts;
- free-text quantity/rate as calculation authority;
- direct free-text owner/rental expense vouchers as the ordinary settlement model;
- deleted-record mirrors as business-history strategy.

## Policy boundary

Historical arithmetic is recorded as **observed precedent**, not universal policy. Partial-month proration, included-KM pooling, replacement charging, downtime, garage mileage, driver/OT qualification, fuel/repair responsibility, accident/insurance excess, deposit policy, exact tax/withholding/rounding and maker-checker requirements remain explicit business decisions.

## Scope and verification

This is documentation-only. No production PHP/TypeScript, migration, route, provider, tenant feature, Reporting runtime, Finance seed or test is changed. The retired Vehicle Rental runtime remains retired. Only `docs/knowledgebase.md` and this new append-only change record are intended to differ from the prior branch head.
