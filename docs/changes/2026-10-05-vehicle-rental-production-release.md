# Vehicle Rental production release — 2026-10-05

## Scope

Final end-to-end repository release of the fresh AutoERP Vehicle Rental implementation using:

- TACGL as the primary Vehicle Rental business/conflict authority;
- all four supplied videos as authoritative practical workflow evidence;
- root `RULES.md` / `AGENTS.md` as engineering constraints;
- `worktree-0.0.8` as the authoritative release-candidate branch;
- `worktree` as the repository production branch.

The removed legacy Rental implementation remains prohibited as an implementation dependency. No removed Rental code is restored, copied, cherry-picked or revived.

## Authoritative release-candidate state

Release-candidate head before this release record:

- branch: `worktree-0.0.8`
- SHA: `2bfb2354dbc31af289b7fb6d4f86563c8a862c99`

That head contains the 2026-10-05 TACGL/video source reconciliation and no newly introduced Rental runtime/schema change.

Open pull requests at release start: none.

## Business-source completion

The 2026-10-05 source reconciliation confirmed:

- canonical `TACGL.zip`: 452 normalized business files;
- dated `TACGL(20261005-130217).zip`: 452 normalized business files;
- normalized relative-path sets are identical;
- differing normalized payloads: zero;
- all four supplied video hashes match the previously audited evidence set;
- no new or conflicting Vehicle Rental business rule was introduced.

Therefore the canonical knowledge base and closed acceptance ledger remain valid.

## Functional completion

`docs/vehicle-rental/TODO.md` remains a closed acceptance ledger with no unchecked product-policy implementation item.

Completed Vehicle Rental capability includes:

- separate Customer/Lessee and Owner/Lessor agreement aggregates;
- immutable commercial revisions/successor lineage;
- agreement-first vehicle selection;
- company-owned and externally supplied vehicle paths;
- effective Vehicle Use / source coverage;
- handover, return and replacement lineage;
- vehicle/driver overlap prevention;
- shared Vehicle / Vehicle-Service availability integration;
- Daily Running Chart operational evidence;
- odometer and mileage continuity;
- driver identity evidence;
- independent Customer and Owner calculations from the same finalized physical evidence;
- base-rent, mileage, typed OT and night-out policies where explicitly defined;
- same-side duplicate source/component consumption prevention;
- Customer Invoice and Owner Payable/Settlement financial handoff;
- Payment-owned deposit/receipt/payment/allocation behavior;
- Tax/Finance/Invoice/Payment ownership boundaries;
- tenant/org isolation;
- optimistic concurrency and deterministic lock discipline;
- immutable histories and correction/reversal lineage;
- human-readable, agreement-first frontend workflow;
- reporting and source traceability.

No evidence-backed Vehicle Rental requirement remains open within the repository/project boundary.

## Current production branch comparison

At release time, production `worktree` already contains the October 4 Vehicle Rental configured-business-time UI finalization and follow-up ambiguity correction.

Before promoting the 2026-10-05 documentation:

- production `worktree` head: `4e2a17a4f1733cccfe95544a23d13ee7817c254f`;
- release-candidate `worktree-0.0.8` head: `2bfb2354dbc31af289b7fb6d4f86563c8a862c99`;
- the branches have different merge history;
- GitHub comparison shows the only release-candidate file delta is:
  - `docs/knowledgebase.md`;
  - `docs/changes/2026-10-05-vehicle-rental-source-reconciliation.md`.

No Vehicle Rental backend, schema, migration, API, financial calculation or production frontend runtime file differs between the two branch tips before this final documentation release.

## Verification evidence

### Latest complete dependency-backed baseline

The latest complete dependency-backed application verification recorded before the October 4 business-time UI delta is:

- Laravel: **881 tests / 9,697 assertions passed**;
- TypeScript typecheck: passed;
- ESLint: passed cleanly;
- Vite production build: passed;
- Vitest: **101 / 101 files, 374 / 374 tests passed**.

Earlier integrated verification of the unchanged Rental backend/schema additionally recorded:

- SQLite backend verification passed;
- MariaDB 10.11.7 / InnoDB backend verification passed;
- clean install passed;
- baseline upgrade passed;
- rollback/reapply passed;
- fresh seed passed;
- 238-table fresh/upgrade/rollback schema metadata parity passed on both engines;
- FK/integrity checks passed.

### October 4 runtime delta verification

The post-baseline Vehicle Rental runtime delta is frontend/shared business-time handling only.

The follow-up correction verified:

- Asia/Colombo business-time conversion;
- New York standard and daylight-saving offsets;
- seconds-preserving round-trip;
- rejection of nonexistent spring-forward local wall time;
- rejection of ambiguous fall-back local wall time;
- removal of the duplicated focused-test import/assertion that would otherwise cause TypeScript `TS2300`;
- no backend/schema/migration/API/tax/posting/financial-calculation change in that correction.

That correction was already promoted to production `worktree` on 2026-10-04.

### Current execution-environment boundary

A fresh full current-head checkout/test run cannot be executed in this environment because DNS resolution for `github.com` is unavailable and the container does not contain the repository dependency tree. Hosted GitHub Actions are intentionally excluded by the free-tools-only project instruction.

No unexecuted test is described as passed.

This limitation does not conceal a release-candidate runtime delta: the current production branch already contains the latest Vehicle Rental runtime correction, and the 2026-10-05 delta is documentation-only.

## Security / architecture release gate

Release review confirms:

- no legacy Rental runtime restored;
- no compatibility workaround preserving a legacy defect;
- no hardcoded Rental tax percentage, statutory threshold or GL account;
- no guessed replacement surcharge, downtime deduction, deposit priority or unsupported tariff;
- no duplicate Rental ledger;
- no second mutable Invoice/Payment source of truth;
- no circular Rental module dependency identified;
- tenant/org boundaries remain authoritative;
- financial owner modules remain responsible for posted financial state and reversal semantics;
- unavailable protected TACGL backup content is not treated as business evidence.

## Repository production action

The safe production action is a normal forward promotion into `worktree`, preserving production history.

No force-push, rebase, history rewrite or unrelated branch merge is required.

After this release record is merged into `worktree-0.0.8`, that exact release lineage is promoted to `worktree`.

## Production readiness boundary

This is a **repository production release**.

It proves the repository release lineage and the available implementation/test evidence. It does **not** claim:

- deployment to an external hosting environment;
- execution of a production database migration against a live database;
- production queue/scheduler/mail/storage/cache connectivity;
- backup/restore drill on the live hosting environment;
- human browser UAT against a live production URL.

Those require an actual connected deployment target and credentials, which are not available in this conversation.

## Decision

**APPROVED for repository production release.**

Within the available project evidence, Vehicle Rental is complete, source-reconciled, architecture-clean, documented and ready for production-branch promotion.

No production runtime code change is required in this release batch; the final delta is release/documentation evidence only.
