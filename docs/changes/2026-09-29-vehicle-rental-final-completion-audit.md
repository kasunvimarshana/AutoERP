# Vehicle Rental final completion audit

Date: 2026-09-29
Branch: `worktree-0.0.8`
Authoritative branch head reviewed at start: `38b2fcd3d11b68a47afe05eeeaec2ff322478ddd`
Runtime implementation baseline: `2c80584446536fa8b1ebfec4c05adbd129706f45`

## Purpose

Perform an independent end-to-end completion review of the fresh Vehicle Rental module against TACGL, all four supplied videos, `RULES.md`, `AGENTS.md`, the canonical knowledge base, the closed implementation ledger, current Rental backend/frontend/schema/tests, and authoritative external legal/technical research. The review must not restore or reuse removed legacy Rental code and must not invent unsupported commercial values.

## Source and business audit

- Revalidated the TACGL archive family. `TACGL.zip` and `TACGL(20260929-141809).zip` contain the same 452 normalized business files with identical per-file SHA-256 values after removing the canonical ZIP wrapper directory. No conflicting business rule was introduced by the dated upload.
- Revalidated all four supplied video hashes/durations against the canonical evidence set.
- Revalidated that TACGL supports Vehicle, Customer/Debtor, Owner/Creditor/Supplier, invoice/transaction, receipt/payment/allocation, cheque/bank, GL/accounting, workshop/service and reporting lineage.
- Confirmed the canonical dual-sided rule remains unchanged: one physical Running Chart can support independent Customer billing and Owner settlement, with separate agreements/rates and independent same-side consumption controls.
- No evidence was found for a new universal tariff, replacement surcharge, downtime credit, garage-KM price, deposit-forfeiture priority, AC fallback, driver-proration rule, tax percentage or withholding threshold that belongs in Rental.

## Protected backup investigation

Nested source archive reviewed:

`DATABACKUP/!   CTACGLDATABACKUP202503271759.rar`

Legitimate free-tool investigation found:

- RAR v2 / solid archive;
- 86 listed entries;
- every listed entry is password protected;
- no archive comment;
- no explicit backup password/passphrase in accessible TACGL text/configuration;
- no explicit passphrase or embedded password-bearing backup command found by targeted string searches of the accessible application/runtime files;
- the accessible legacy `password.DBF` is application-user credential data and the evidence does not link its values to the backup archive password.

No brute force, dictionary attack, arbitrary password mutation or unsupported credential guessing was used. The backup therefore remains unavailable evidence, but it is not a blocker because runtime behavior is fully governed by documented named policies and owner-module boundaries rather than guessed hidden logic.

## Current implementation review

The current implementation was reviewed across services, models, routes, migrations, permissions, frontend surfaces and tests.

Confirmed implemented boundaries include:

- separate Customer and Owner Agreement aggregates;
- Draft/Active/Closed lifecycle and immutable commercial revisions;
- one-way successor lineage with scoped database integrity;
- agreement-first vehicle selection;
- company-owned and externally supplied vehicle paths;
- tenant/org scoped application validation;
- physical handover/return/custody and one-way replacement predecessor lineage;
- shared Vehicle/Vehicle-Service availability contract;
- Running Chart Draft/Finalize/Reverse/Correction lifecycle;
- immutable finalized physical evidence;
- authoritative employee/external driver evidence and overlap controls;
- exact odometer/commercial-KM/garage-KM separation;
- named base-rent policy `actual_calendar_days_v1`;
- named mileage-cycle policy with independent Customer/Owner pools;
- typed OT/night-out calculations with no inferred qualification rules;
- no implicit AC fallback or automatic unsupported AC charge;
- independent Customer and Owner source calculations;
- immutable Rental source charges and same-side duplicate-consumption protection;
- Invoice/AP ownership for financial documents;
- Payment ownership for deposits, Customer Receipts and Owner Payments;
- Tax ownership for effective-dated tax/withholding treatment;
- Finance ownership for posting profiles, journals, periods and bank reconciliation;
- semantic Rental permissions and tenant-feature gating;
- human-readable operator UI with no ordinary raw-ID workflow.

## Relationship review

No relationship rewrite is justified by this audit.

Retained intentionally:

- Customer Agreement and Owner Agreement remain separate because they represent different counterparties and economic obligations.
- `successor -> predecessor` remains one-way; storing an inverse pointer would duplicate revision state.
- replacement `Vehicle Use -> predecessor Vehicle Use` remains one-way; replacement commands resolve both uses through the same tenant/organization/customer context and exact physical boundary.
- Running Chart correction `correction -> original` remains one-way; an inverse relationship would be redundant.
- Running Chart -> HR employee remains one-way; HR does not depend on Rental.
- Rental does not duplicate Invoice, Payment, Tax, Finance, Vehicle or Vehicle Service ledgers/master state.

The database self-links prevent cross-tenant linkage, while application commands additionally resolve records through the same organization context and immutable parent relationships. Expanding every self-link into broader new composite constraints without an observed defect would add migration complexity without changing valid application behavior, so no speculative schema hardening was introduced.

## External research reconciliation

Authoritative external sources were used only to validate ownership/engineering decisions, not to invent TACGL terms:

- Sri Lanka IRD 2026 tax-invoice/circular changes reinforce keeping statutory invoice/tax behavior effective-dated in Invoice/Tax rather than hardcoding Rental constants.
- Sri Lanka IRD withholding material reinforces evaluating withholding in Tax/Payment using actual party/payment/statutory-period facts rather than a Rental-local percentage.
- IFRS 15 contract-modification guidance supports preserving historical economics and prospective revision lineage; it does not define an AutoERP Rental tariff.
- MySQL InnoDB guidance supports short transactions, stable lock ordering and whole-command retry after deadlock; current Rental commands already use transactional state transitions and stable lock ordering at shared physical-resource boundaries.

No new automatic Rental monetary rule was introduced from external research.

## Verification evidence

No runtime file, schema, API, permission or frontend file changed during this completion audit. The current branch differs from runtime baseline `2c80584446536fa8b1ebfec4c05adbd129706f45` only by documentation commits.

The exact runtime baseline already has recorded executed verification in `docs/changes/2026-09-29-rental-executable-verification.md`:

- Laravel / SQLite: **820 tests / 8,913 assertions passed**;
- frontend Vitest: **88 files / 327 tests passed**;
- TypeScript: passed;
- ESLint: passed;
- production Vite build: passed;
- fresh SQLite migration + seed: passed;
- rollback/reapply of the September 25 Rental upgrade migrations: passed with the Rental schema/FK/index metadata restored and `foreign_key_check` clean;
- MySQL grammar compilation of the changed constraint migrations: passed.

Because this audit introduces no runtime delta, that executed verification remains applicable to the current runtime implementation. This review does not claim that SQLite proves real InnoDB contention or that a repository test suite substitutes for deployment-environment backup/restore and browser UAT.

## Completion result

- `docs/vehicle-rental/TODO.md` remains a closed acceptance ledger, not an open backlog.
- No evidence-backed unfinished Vehicle Rental runtime requirement was found.
- No old Rental implementation was restored, copied or referenced as a runtime dependency.
- No magic tariff/tax/account values were added.
- No unnecessary relationship, compatibility layer, duplicate ledger or speculative feature was introduced.
- No open pull request currently targets `worktree-0.0.8`.

The Vehicle Rental module is therefore closed at repository implementation/automated-acceptance level on the reviewed runtime baseline. Future changes require new business evidence, a defect reproduction, or a changed legal/owner-module contract; they must not reopen historical ambiguity by guessing.
