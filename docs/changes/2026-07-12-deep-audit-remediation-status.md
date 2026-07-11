# Deep-audit remediation status

Date: 2026-07-12
Branch: `worktree-0.0.8`

## Completed in this remediation pass

- Moved Tenant and platform-operator seeder environment reads into their owning module configurations.
- Added behavioral regression tests for cached seeder configuration.
- Corrected the Vehicle Rental activation contract: execution date and legal context are required; printable clauses remain optional.
- Added independent activation rejection tests that prove failed commands do not mutate status, row version, or document snapshots.
- Hardened Invoice, Payment, TaxTransaction, and FinanceJournalEntry against broad mass assignment.
- Kept authoritative financial writes inside Invoice, Payment, Tax, and Finance owner services.
- Updated the two direct Payment fixture builders without weakening the production model.
- Added boundary tests for each hardened financial model.
- Added a guarded MySQL/MariaDB PHPUnit profile with no committed credentials and a mandatory disposable `_test` database target.

## Verified as already implemented or not currently actionable

- Vehicle Service route entitlements already use granular feature-owned permission mappings.
- Finance, Tax, and Invoice route entitlements already take precedence through feature-owned registries.
- No open pull requests were available to merge into `worktree-0.0.8` during this pass.

## Remaining release blockers requiring explicit design or deployment evidence

1. **Schema lifecycle** — decide whether every existing database is disposable. If any persistent database has executed rewritten baseline migrations, forward-only upgrade migrations and a released-schema upgrade test are required.
2. **Accounting periods** — define close, soft-close, reopen, backdated-posting, and reversal policies before adding the Finance-owned period aggregate and enforcement.
3. **Runtime health** — define deployment order and heartbeat expectations for workers and scheduler before changing `production:check` from configuration readiness to operational readiness.
4. **Vehicle Rental concurrency** — correct and verify running-chart lock order against MySQL/MariaDB with concurrent requests; SQLite cannot prove the behavior.
5. **Full model-boundary migration** — many non-financial models still override Core's deny-by-default guard. Convert them owner-module by owner-module only after every production and fixture creation path is identified.
6. **Legacy test wording** — the large RentalAgreementCreateTest still contains an older method name referring to printable terms. The new tests establish the correct behavior, but the stale method should be renamed when that file can be safely patched and fully executed.
7. **Production infrastructure** — worker, scheduler, cache, storage, mail delivery, backup, and restore readiness still require environment-level verification.
8. **Business scope** — Sales/Quotation and full HR/payroll scope require stakeholder confirmation; they must not be guessed into Invoice, Purchase, or HR modules.

## Release statement

The source changes in this pass are targeted hardening improvements. They do not justify labeling the whole application production-ready until the normal full suite, the guarded MySQL/MariaDB suite, and the environment-level operational gates pass.
