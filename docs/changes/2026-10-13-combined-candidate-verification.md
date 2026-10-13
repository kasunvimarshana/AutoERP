# Combined release candidate verification — 2026-10-13

## Integration and fixes

Target remains `worktree-0.0.8` at `0b28bb6372d5ada787a514e020080aa2cb8c2fc8`. PR #127 remains the only open PR.

The candidate preserves the previously published CI/security/UI fixes (`2566197ea7b3d4306b4e874ac2212c1bbb016a7e`) and integrates these source heads:
- `sell-module`: `ce67a509ac1d0960c745ec2f4c866d839fc0c8cf`.
- `agent/production-release-hardening-20261006`: `dade9d53f05f9d7ce60d983d80d155c288029cd8`.
- `agent/vehicle-rental-final-integrity-20260928`: `b0802b253375bab7366be49e80c9db99a73ae222`.

Customer outstanding reporting belongs to Reporting; API routes, request/controller/service/enum and tests were moved together to remove a Finance dependency cycle. The integrated seller date/credit filters retain the corrected party discriminator, grouped customer projection, case-normalized currency and scoped customer identity. Pending UI requests cannot replace a newer filter result. The inventory index and service credit-mode schema changes have explicit fresh baselines and identity-preserving upgrade migrations.

Payment corrections preserve invoice FX snapshots on repeated backfill, fix refund API function shadowing, and allow whole-payment reversal of historical allocations through their original payment journal. Independent allocation reversal retains its historical-data guard. Regression coverage verifies the old payment journal and its reversal net to zero per account. Fixtures now distinguish Finance-owned reversal from source-owned reversal and account for independent allocation journals.

Rental replacement input is fully validated before custody mutation. Cutovers distinguish actual physical custody, mileage commercial cycles and non-mileage chart evidence. Conflict resolution preserves the newer shared tenant-calendar supply-period snapshots.

## Executed verification

Application code and tests were verified at local integration commit `7a5249bb4`; the following release-record changes are documentation only. The publication process checks exact Git tree equality before updating PR #127.

| Check | Result |
| --- | --- |
| PHP 8.3 / SQLite full suite | 919 tests, 10,080 assertions passed |
| PHP 8.3 / MySQL 8.4.11 full suite | 919 tests, 10,080 assertions passed |
| Vitest | 116 files, 432 tests passed |
| TypeScript / ESLint | Passed, no lint warnings |
| Vite production build | Passed |
| Composer strict validation | Passed |
| Composer locked audit / npm audit | No advisories / zero vulnerabilities |
| SQLite fresh migration / complete rollback / reapply | Passed |
| MySQL fresh migration / complete rollback / reapply | Passed in isolated temporary data directory |
| Formatting of changed PHP / git diff whitespace | Passed |

The first MySQL attempt encountered an existing temporary tablespace during schema setup (918 tests passed, one setup error). Reinitializing a separate disposable MySQL data directory produced the successful complete result above, without application or test changes. A subsequent workspace-backed migration reapply also encountered an existing tablespace after successful rollback. The full fresh/rollback/reapply sequence then passed in an isolated temporary data directory, with unchanged migrations. The workspace-backed failure is retained in the execution history; the passing isolated result is not evidence of populated production upgrade safety.

## Branch and PR audit boundaries

The remote inventory contains 591 real branches (excluding the symbolic origin HEAD). At the local verification snapshot, 498 tips are ancestors and 93 are non-ancestors, including the equivalent API-published checkpoint whose commit identity differs from its local counterpart. These counts are history classification, not blanket semantic approval. The PR inventory contains 120 merged PRs, five closed unmerged PRs and one open draft PR (#127). Closed superseded PRs #123/#126 are not counted as merged.

Older driver-schema, calendar/audit, business-time and temporary branches contain overlapping or superseded implementations. For example, the driver fields already exist in the canonical upgrade path, and the temporary branch's closed-agreement coverage is enforced through RentalCalendar. Blind integration would duplicate schema changes or discard newer UI/calendar behavior. Remaining divergent historical changes have not all received a file-by-file semantic approval; they must not be described as all integrated.

## Release gates still open

GitHub Actions last reported an account billing lock before any job steps executed (issue #124). Local green checks do not replace required hosted checks. The owner's existing review explicitly says NOT APPROVED and requires section R acceptance evidence. PR #127 stays draft and unmerged until those gates pass.

Section R's nine unchecked gates remain unchecked: governed incident attachments and approved liability/disputes; complete customer/owner correction-note lifecycles and tax snapshots; all credit/note Finance and FX reconciliation; Purchase-note currency provenance; all approved Payment scenarios; agreement-to-document-to-settlement reporting; populated upgrade and representative InnoDB contention validation; business UAT and deployment smoke checks. No production release, deployment, tag, invented financial policy or approval bypass occurred.
