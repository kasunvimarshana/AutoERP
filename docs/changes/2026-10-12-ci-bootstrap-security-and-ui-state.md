# CI bootstrap, dependency security and UI request ownership — 2026-10-12

## Scope and cause

Continued PR #127 from `7a6450cb4ba01106f61c9e2803c7b40d301edd71` without changing the authoritative target branch.

- Composer package discovery runs before PHPUnit sets its test environment. The CI backend jobs now explicitly set `APP_ENV=testing` and copy `.env.example` before dependency installation. This resolves the reproduced central-host bootstrap failure and missing-environment warnings without relaxing production tenant validation.
- Updated Composer and npm lockfiles within the existing manifest constraints to remove reported dependency vulnerabilities. CI now runs the existing dependency-audit scripts.
- Corrected the Vehicle Service status workflow test to assign an employee to its active service line. The production workforce requirement remains enforced.
- Customer credit profile and Selling route editors now own state per customer/route. A late save or return response cannot replace the newly selected customer's/sale's form. The Selling business date is evaluated when the form opens or the return is submitted rather than when the JavaScript module first loads.
- Rental incident selectors use the shared cancellable request hook, preserving Vehicle Use/Running Chart scoping and clearing obsolete request state.
- Added regression tests for customer navigation during a pending save, sale navigation during a pending return, and stale incident-chart responses. The first two reproduce failures against the prior implementations.

## Executed verification before further integration

- PHP 8.3 / SQLite: **898 tests, 9,899 assertions passed** against this worktree and updated Laravel lockfile.
- The preceding work session also executed the same backend suite on MySQL 8.4.11: **898 tests, 9,899 assertions passed**. These results predate the newly discovered October 12 `sell-module` commits and the pending financial-hardening integration; they must not be attributed to those changes.
- Frontend: **114 files, 429 tests passed**; TypeScript, ESLint (no warnings), and Vite build passed.
- npm dependency audit: **zero vulnerabilities**. The previous session's final Composer audit also reported zero; audit both final lockfiles again before promotion.
- `git diff --check` passed. No application migration or financial rule is changed by this commit.

## Hosted CI and remaining acceptance

GitHub run `38163363955` did not execute any job steps. The Backend SQLite check annotation explicitly reports: “The job was not started because your account is locked due to a billing issue.” Issue #124 records this account-level blocker. The environment fix in this commit does not remove that lock, and local passes do not constitute green hosted checks or independent approval.

PR #127 remains draft. The nine unchecked extended financial/release gates in `docs/vehicle-rental/TODO.md` §R remain open. Further branch integration, migration/upgrade checks, InnoDB contention, financial acceptance, and deployed UAT/smoke verification must be recorded separately. No target merge, production deployment, or release tag is claimed.
