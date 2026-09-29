# Rental executable verification and successor custody boundaries

Date: 2026-09-29

Baseline: `c87010bd2281e8a8ce24c82f36e15521ac944c8a` on `worktree-0.0.8`, independently checked through GitHub and a new checkout. Scratch maintenance had removed the previous checkout, runtime and uncommitted whole-distance experiment. The newer authoritative branch already contained driver identity, successor and calendar work. This pass therefore verified that current implementation before adding tariffs. No removed Rental code was retrieved or reused.

## Failures reproduced and corrections

Dependency-backed SQLite execution failed before feature assertions: the successor constraint-hardening migration dropped foreign keys by name alone. Laravel's SQLite grammar requires the local column list. Supply both columns and the original explicit constraint name; SQLite uses the former, MySQL compiles the latter. Apply the same portable form to driver and successor rollback paths. Expand successor DDL explicitly instead of deriving table/constraint names in loops.

Move closure and successor-hardening alterations from `Database/Migrations` into the already-registered `Database/UpgradeMigrations`. The architecture test requires the former directory to contain the one-table creation baseline. Preserve published filenames: Laravel's migration identity is the filename, so an already-applied migration is not replayed merely because its directory changed. Keep the published two-table migration identities rather than replacing them with new filenames that would duplicate changes on installed databases. New schema work should continue to use one table per migration. No database column, business relationship or data is removed by these source-file moves.

Seven new successor boundary scenarios reproduce six failures before the service fix. Compare successor midnight in Configuration's tenant/org timezone, not the submitted timestamp offset. Planned uses use planned end; returned uses use actual return; still-open custody blocks cutover even after planned return. Exact midnight adjacency is valid. Early actual return can release a later or open-ended plan; late actual return cannot be hidden behind an earlier planned end. Test both customer and owner successors and verify rejected cutovers preserve predecessor Active, successor Draft and history counts.

Keep existing strict commercial-coverage enforcement. Older usage arithmetic and owner-source tests used client-offset midnight before the fixture's configured UTC agreement start; align their fixtures to UTC. Run foreign-tenant lookup assertions in a separate tenant execution context rather than an invalid nested ownership switch. No authorization, tenant scoping or commercial validation was weakened to make tests pass.

## Ownership and relationship review

Rental owns successor activation and custody coverage. The fix reads existing immutable planned times and existing actual-return state under the agreement/use locks. No new inverse relationship, cross-module workaround, financial ledger, tariff, tax assumption or magic monetary value is introduced. The same existing composite tenant/org successor FK and uniqueness semantics remain intact.

## Executed verification

- Full PHP/SQLite suite: **820 tests, 8,913 assertions**, all passed.
- Full frontend suite: **88 files, 327 tests**, all passed.
- TypeScript, ESLint, Vite production build, changed-file Pint and whitespace checks passed.
- Fresh SQLite migration passed, followed by successful seeding.
- Rolled back and reapplied all four September 25 Rental upgrade migrations. All **12 Rental tables** have matching column, FK and index metadata before/after; `foreign_key_check` reports no violations.
- Compiled the three changed constraint migration up/down paths through Laravel's MySQL grammar in pretend mode. Generated drops retain `vrc_driver_employee_fk`, `vrca_successor_fk` and `vroa_successor_fk`. This is SQL compilation, not execution against MySQL.
- Read the final service/test/schema diff and preserved the existing architecture baseline test rather than adding exemptions.

Free Ubuntu PHP packages were extracted into scratch with package-metadata SHA-256 validation; Composer and npm used repository lockfiles. Initial runtime setup and regression failures were resolved before the final passing runs. No paid tools, GitHub Actions, force push or production deployment.

Real InnoDB contention, production-data upgrade and human browser/UAT acceptance were not executed. The prior remote-only completion records are historical static-review evidence; they do not replace the concrete execution results above. Knowledge base and acceptance ledger now distinguish that evidence accurately. The unpublished whole-distance experiment is not part of this delivered change.
