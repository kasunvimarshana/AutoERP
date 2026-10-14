# Local Purchase allocation and upgrade verification — 2026-10-14

## Source and publication boundary

Tested application source: PR #127 head `cc6c884e1660254db3064f04837c7861f54a2eb0`. This follow-up adds test coverage and documentation only. It does not change application code, financial records, schema definitions, workflow configuration, or release requirements.

The user's current instruction prohibits GitHub Actions, paid services and additional credits. The existing `pull_request` workflow runs on synchronization of PR #127; pushing to `worktree-0.0.8` also triggers it. Therefore this work is published on `local-verification/release-20261014`, with **no pull request opened and no update to PR #127's head**. That branch is outside the workflow's two push branch filters. Local verification does not create or impersonate required GitHub check results.

## Ref and review verification

A fresh remote fetch still finds 591 branches before publishing this new branch: 499 tips are ancestors of the tested PR head and 92 are not. This is an inventory and ancestry classification, not a semantic review or approval of every historical change. `worktree-0.0.8` at `0b28bb6372d5ada787a514e020080aa2cb8c2fc8`, `worktree` at `c7519acab6f66e89757b970735dc678757f2cb2b`, and `sell-module` at `ce67a509ac1d0960c745ec2f4c866d839fc0c8cf` are all ancestors of the candidate (0 commits ahead). Those tips require no further integration conflict resolution. GitHub still lists one open PR (#127) and two open issues (#124 and #128). No open PR has an approving review, so none is eligible for the requested approved-only merge.

## Added executable evidence

`tests/Database/PurchaseReleaseVerificationTest.php` uses disposable MySQL databases and the real Purchase/Invoice services:

1. Two separate PHP processes compete to allocate the same posted note. The parent observes the contender in MySQL `performance_schema.data_lock_waits` before releasing the holder. Exactly one allocation succeeds; the stale version is rejected, with note and invoice amounts conserved.
2. Two different notes compete to allocate more than one invoice's remaining balance. The second transaction is rejected after actual InnoDB contention; its note remains unallocated and its version unchanged. Exactly one Invoice-owned credit allocation exists.
3. A populated debit-note currency migration is rolled back, reapplied and reapplied again. Original monetary fields, version, timestamps and credit-allocation rows are preserved; historical currency stays NULL and further allocation is rejected. All five published reconciliation SELECTs execute on MySQL. The valid fixture has no scope/conservation anomaly; the unresolved historical allocation is subsequently reported as `unresolved_currency`, without changing its amounts.

`tests/Support/purchase-allocation-worker.php` is the child-process helper. Both the parent command and child use the existing MySQL bootstrap guard: explicit mysql/mariadb connection, empty DB_URL, and a disposable database name ending `_test`. The suite is intentionally an explicit release-verification command, outside the default SQLite/MySQL unit/feature suites; it needs MySQL 8 performance-schema lock visibility and permission to recreate the disposable schema.

## Reproduce locally, without CI

Use the repository's locked Composer/npm dependencies, PHP 8.3 with BCMath and PDO SQLite/MySQL, and an isolated MySQL 8.4 instance. Set `APP_ENV=testing`, `DB_CONNECTION=mysql`, local `DB_HOST`/`DB_PORT`, a **disposable** `DB_DATABASE` ending `_test`, its test-only credentials, and `DB_URL=`. Never point these commands at production or a valuable restored dataset: Laravel's test lifecycle recreates tables.

```sh
php vendor/bin/phpunit --configuration phpunit.mysql.xml tests/Database/PurchaseReleaseVerificationTest.php --no-progress
composer test:mysql -- --no-progress
```

Run the ordinary SQLite/backend, frontend and audit checks separately:

```sh
php vendor/bin/phpunit --no-progress
python -B -m unittest discover -s docs/audits -p 'test_*.py' -v
composer validate --strict
composer security:audit
npm run security:audit
npm run typecheck
npm run lint
npm test
npm run build
php vendor/bin/pint --test tests/Database/PurchaseReleaseVerificationTest.php tests/Support/purchase-allocation-worker.php
```

Fresh migration, full rollback and reapply were also run on separate disposable databases using `artisan migrate:fresh`, `artisan migrate:rollback` and `artisan migrate`, each with `--force --no-interaction` and an explicit isolated test connection.

## Actual results

| Check | Local result |
| --- | --- |
| PHP 8.3.6 / SQLite full suite | 921 tests, 10,110 assertions passed |
| PHP 8.3.6 / MySQL 8.4.11 full suite | 921 tests, 10,110 assertions passed |
| MySQL 8.4.11 contention/populated-upgrade/audit suite | 3 tests, 46 assertions passed |
| Python audit regressions | 5 passed |
| Vitest | 116 files, 432 tests passed |
| TypeScript, ESLint, Vite production build | Passed |
| Composer strict validation | Passed |
| Composer locked security audit | No vulnerability advisories |
| npm security audit | Zero vulnerabilities |
| SQLite fresh migration / full rollback / reapply | Passed |
| MySQL fresh migration / full rollback / reapply | Passed in a fresh isolated temporary data directory |
| New PHP-file formatting | Passed |

The first contention-suite run had one failing test assertion: it expected the word `remaining`, while the actual correct rejection was `Credit allocation cannot exceed invoice balance.` The assertion was corrected to the service's contract; the passing run above also adds the MySQL audit-query assertions. No application behavior was altered to obtain a green result. The final assertions also inspect the actual `expected_version` validation error and require the winning note version to advance exactly once, instead of relying only on a worker-generated rejection label.

## What remains unverified or incomplete

- These are synthetic fixtures, not a historical financial reconciliation. No authorized restored production-like dataset was available. Issue #128 still requires source-document currency evidence, historical duplicates/allocation review and Invoice/Finance/GL tie-out. The tested Purchase allocation path updates the note and Invoice subledger; this evidence does **not** establish balanced Finance journals or complete period/FX/tax/reversal behavior.
- The populated test covers the debit-note currency upgrade only. It does not establish all populated schema upgrades, Purchase Return currency upgrades, source-uniqueness upgrades with historical duplicates, or rollback safety for real data.
- Contention covers two Purchase allocation scenarios. It does not establish concurrency safety of every financial, Rental, payment or reversal path.
- All nine Vehicle Rental TODO section R gates remain open in aggregate: governed attachment/liability/dispute implementation; customer/owner adjustment documents and snapshots; all Finance/FX/tax paths; historical currency reconciliation; complete approved Payment scenarios; settlement reporting; broader upgrade/concurrency/authorization/UAT acceptance; final financial tie-out and deployment smoke. Existing passing tests do not prove missing workflows are implemented.
- PR #127 remains draft. The review is COMMENTED and explicitly NOT APPROVED; no approving review exists. Issue #124 remains an external required-check/account-policy blocker. Current integration permissions cannot read branch-protection settings; absence of visible rulesets is not a waiver.
- Free alternatives are the commands above and read-only SQL against an authorized isolated restored dataset, followed by accountable Finance/business UAT and independent review. A repository administrator must determine whether an authorized non-Actions local verification mechanism can satisfy the actual mandatory check policy. No check names/statuses, approvals or branch protections are fabricated or weakened.

No merge, production deployment, release tag, production financial mutation or claim of production readiness is made by this record.
