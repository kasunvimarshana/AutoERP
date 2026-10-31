# User-executed local regression after Tenant private-file hardening — 2026-10-31

## Evidence identity and integrity

The user supplied the raw Windows console transcript `Pasted text(20261031-180822).txt` (1808 text lines), SHA-256:

`73eec5c3f500d11e43b357403afc86ef400d7fe9fb1174051639b2c7d956f874`

It shows the local workspace `D:\\project-new\\AutoERP` on `local-integration/release-20261031` with `git status` reporting a clean worktree and tracking `origin/local-integration/release-20261031` at the beginning **and** end. This transcript is user-supplied execution evidence, **not execution by the GitHub connector or this server**.

The console shows these commands and results:

| Executed locally | Visible result |
|---|---|
| `git status`, `git branch` | Candidate branch, upstream up-to-date, no pending worktree changes. |
| `php artisan test` | **958 passed / 10,634 assertions**, duration 159.15 seconds. |
| `Tests\\Feature\\Tenant\\TenantPrivateFileServiceTest` | New private-upload filename regression **passed**. |
| `npm run typecheck -- --pretty false` | No errors displayed. |
| `npm run lint` | No errors displayed. |
| `npm run test` | Vitest **129 test files / 468 tests passed**. |
| `npm run build` | Vite 7.3.6, 724 modules transformed, **built in 8.22 seconds**. |
| Final `git status` | Clean, upstream up-to-date. |

Parsing the supplied transcript confirmed all eight command prompts are present, no `FAIL`/`FAILED` test lines, and a completed Vite build marker. No command exit-code capture or `git rev-parse HEAD` is present. Thus the transcript ties results to the named *up-to-date candidate branch* at execution time, **not cryptographically to an exact commit**. The GitHub candidate was `c231e7c187cedce24a70d10f13134b4b9d8bf5e4` before this evidence-only document commit, but the local log alone does not prove its exact checkout SHA. An exact-head release attestation remains required.

## Explicit limits

- `php artisan test` is the regular configured test suite; this transcript does **not** show which database driver/profile was used. Do not label this run as both SQLite and MySQL.
- No user-executed `php artisan migrate:fresh`, complete rollback/reapply on populated data, actual InnoDB contention, `composer audit`, `npm audit`, historical source-currency/FX/tax/accounting ledger reconciliation, production UAT, or deployment smoke appears in **this** transcript. Earlier dated records cover separately executed historical tests on earlier commits, without establishing this run's exact-head release readiness.
- The transcript does not resolve failed PR #127 hosted checks, independent approval, issue #124 (Actions account/check policy), issue #128 (historical Purchase allocation/Finance/GL tie-out), or the nine unchecked Rental TODO §R financial acceptance gates.
- The new regression test's pass supersedes the previous **lack of execution evidence for that test**, but does not authorize deployment or merge.

## Safe release procedure

Continue non-Actions local testing using the user's existing free Windows checkout and a separately disposable database. The release verification should print `git rev-parse HEAD` before/after running commands, show explicit success exit codes and active `DB_CONNECTION` profile, and record authorized isolated historical Finance tie-out separately. Do not use GitHub Actions, a paid runner, a production database for test resets, or bypass branch protection/review.

This record is append-only documentation; application code, credentials, financial data, PR head and authoritative release target are unchanged.
