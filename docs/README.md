# AutoERP Architecture and Release Evidence Index

This file is the canonical entry point for current architecture, remediation, release evidence, and unresolved product decisions. Historical files under `docs/changes` are append-only records of individual changes; they do not independently prove current production readiness.

## Authoritative branch

- Repository: `kasunvimarshana/AutoERP`
- Branch: `worktree-0.0.8`
- Other worktree and feature branches must be compared against this branch before integration.
- A branch is not merged solely because it contains newer commits. Changes must preserve bounded data access, module ownership, tenant isolation, financial integrity, and current regression fixes.

## Architecture foundations

- Shared-schema multi-tenancy with explicit tenant execution context.
- Organization-unit scope is explicit and cannot be inferred from request payload authority.
- Business modules provide semantic accounting facts; Finance resolves effective accounts and owns journals, ledger entries, periods, posting profiles, and reversals.
- Invoice owns canonical commercial documents, immutable snapshots, balances, posting plans, and governed lifecycle transitions.
- Inventory owns quantities, movements, valuation layers, reservations, allocations, and movement cost.
- Tax owns tax calculation/snapshot facts but source coordinators bind Tax and Finance atomically.
- Source modules own restoration of their source links and quantities when an Invoice is cancelled, voided, or reversed.

## Verified baseline before the latest hardening batch

The most recent user-executed full verification before the Finance source/profile and Vehicle Service COGS hardening reported:

- Laravel default profile: 669 tests, 8,354 assertions, all passed.
- Laravel MySQL profile: 669 tests, 8,354 assertions, all passed.
- TypeScript: passed.
- ESLint: zero errors, 15 warnings.
- Vite production build: passed.
- Vitest: 69 files and 256 tests, all passed.
- Working tree: clean and synchronized with `origin/worktree-0.0.8`.

These results do not automatically verify later commits. Rerun the gates below after pulling the latest branch.

## Current financial integration status

Implemented foundations include:

- atomic Invoice, Tax, and Finance posting;
- governed Invoice reversal with source restoration;
- semantic Payment posting for receipts, supplier payments, advances, deposits, allocations, and reversals;
- Purchase GRN Inventory/GRNI posting and supplier Invoice clearing;
- accounting-period close/reopen enforcement at journal posting and reversal boundaries;
- canonical Finance source identity and exact-once posting;
- manual journal separation from business-source identity;
- effective-dated posting profiles and account-role assignments;
- posting-profile fallback visibility and optimistic concurrency;
- Vehicle Service Invoice posting and parts-consumption COGS posting;
- Vehicle Rental commercial Invoice posting and source restoration;
- Vehicle Finance installment Invoice-link restoration.

## Open policy-dependent items

The following items must not be implemented by assumption:

### Vehicle Finance GL policy

A stakeholder-approved accounting policy is required for:

- vehicle asset recognition ownership;
- initial deposit handling and Payment linkage;
- financed principal liability recognition;
- principal reduction at installment billing/payment;
- interest and fee recognition timing;
- tax treatment and reversal behavior.

Until that policy exists, Vehicle Finance payable creation remains Draft-only. This prevents principal from being incorrectly treated as rental expense or the vehicle asset from being recognized twice.

### Product scope decisions

The following remain product decisions rather than coding defects:

- Payroll module scope;
- Sales quotation/order/delivery/counter-sales scope;
- Vehicle Rental replacement, downtime, free-kilometre, and deposit-priority rules.

## Operational release gates

Automated code tests do not replace environment verification. A production release requires evidence for:

- forward-only migration rehearsal against a disposable copy of the target schema;
- backup creation and restore test;
- queue worker liveness and failed-job handling;
- scheduler liveness and overlap protection;
- external mail delivery;
- cache connectivity and eviction behavior;
- private storage read/write/delete/reconciliation;
- TLS, secrets, environment isolation, and least-privilege database credentials;
- critical workflow UAT and rollback procedure.

## Required verification commands

```bash
git checkout worktree-0.0.8
git pull origin worktree-0.0.8
git diff --check

php artisan migrate:fresh --seed
php artisan test
composer test:mysql

npm run typecheck -- --pretty false
npm run lint
npm run build
npm run test

git status
git log -10 --oneline
```

For deployed-schema rehearsal, run the Finance upgrade migrations against a disposable database copy before production deployment. The canonical source-key migration intentionally fails when historical journals contain conflicting business-source identities; such conflicts require investigation, not automatic deduplication.

## Recent remediation evidence

Start with these append-only records:

- `docs/changes/2026-07-13-finance-source-profile-and-service-cogs-hardening.md`
- `docs/changes/2026-07-13-frontend-toast-and-lifecycle-test-alignment.md`
- `docs/changes/2026-07-13-posting-date-and-grn-reversal-contract-fix.md`
- `docs/changes/2026-07-13-finance-posting-regression-remediation.md`
- `docs/changes/2026-07-13-finance-test-fixture-alignment-followup.md`

When this index and an older change record disagree, this index and the current authoritative branch take precedence.
