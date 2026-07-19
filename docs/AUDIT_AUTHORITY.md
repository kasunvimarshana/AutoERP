# AutoERP Audit Authority

This document defines how audit reports, video-derived knowledge, change records, tests, and current code should be interpreted.

## Authoritative order

For the current system state, use this order:

1. Latest `worktree-0.0.8` production code and migrations.
2. Behavioral tests executed against the same commit and database engine.
3. Latest append-only records in `/docs/changes`.
4. Current architecture and product specifications.
5. Historical project audit reports.
6. Legacy-system video audit reports.

A historical audit finding is not automatically a current AutoERP defect. It must be reverified against the latest authoritative branch.

## Required status labels

New audit and remediation records should classify findings as:

- **Current defect** — reproduced in current code.
- **Verification gap** — current behavior has not been proven in the required environment.
- **Resolved** — corrected and linked to a change record and verification evidence.
- **Historical evidence** — describes a legacy system or an older AutoERP commit.
- **Decision required** — implementation would require an unapproved business or deployment rule.
- **Not applicable** — valid evidence that does not apply to current scope.

## Legacy Vehicle Rental evidence

Legacy Vehicle Rental videos remain valuable business evidence for agreements, allocations, Running Charts, customer billing, owner settlements, payments, deductions, reconciliation, and reporting.

They are not authoritative evidence that current AutoERP has the same architecture or defects. Every legacy issue must be mapped to current code before remediation.

Rules that the videos do not prove—such as partial-month proration, replacement charging, downtime deductions, free-kilometre pooling, garage-mileage billing, accident excess, deposit priority, and multi-driver splits—remain decision-required. They must not be implemented by assumption.

## Verification language

Use precise statements:

- “Source review passed” means changed source and boundaries were inspected.
- “Focused tests passed” requires the named commands to have run on the stated commit.
- “Full automated gates passed” requires SQLite, MySQL, frontend tests, typecheck, lint, and build on the same commit.
- “Production ready” additionally requires migration rehearsal, infrastructure checks, backup/restore evidence, browser smoke, and business UAT.

Do not use a historical green test result to certify a later commit.

## Current unresolved categories

The following categories cannot be closed by speculative code:

- production database upgrade policy for already-released schemas;
- real parallel MySQL concurrency evidence;
- staging browser E2E and role accounts;
- live queue-worker, scheduler, SMTP, cache, storage, backup, and restore verification;
- Customer credit exposure and audited over-limit override policy;
- unresolved Vehicle Rental commercial rules.

These remain explicit release or decision gates until evidence is supplied.
