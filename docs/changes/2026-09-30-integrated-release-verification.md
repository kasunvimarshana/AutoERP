# Integrated release verification

Date: 2026-09-30

## Scope and ancestry

Integrate `sell-module` at `c4582a5af235371619e1c478e0e60da5f06cbcf4` with the authoritative `worktree-0.0.8` at `5c8aa6ef967c42bad865e55c9c5ed44c2f1ef92c` and `worktree` at `4341a92914b61713ea197bd215bc77f847888bf8`. The requested `worktree-0.0.0.8` ref does not exist. `worktree` already contains the authoritative head and has the same file tree. The integration is a real merge preserving both parents, not a file-copy replacement of branch history.

Preserve the fresh Rental implementation, its source audit and closed policy ledger. Do not restore the obsolete Rental implementation or its removed routes/profiles from the Selling branch. No new Rental tariff, automatic liability, tax rate or unsupported business rule is introduced. The existing protected-backup investigation remains the recorded result; no recovered password or new archive access is claimed.

## Integration corrections

- Preserve the authoritative Rental lookup, financial handoff and dialog accessibility behavior while integrating Selling, Expense, Inventory batch pricing, Vehicle Service, reporting and master-data improvements.
- Add Inventory batch `row_version` and Vehicle Service batch/pricing references through explicit upgrade migrations. Changing already-run creation migrations would leave installed databases without these columns. Register alterations in each owning module's existing migration loading pattern.
- Move incoming alteration migrations into owner-module `UpgradeMigrations`, preserving their published filenames so Laravel does not replay installed changes.
- Add scoped unique keys on new sale-return and imported-service-history child tables. Their tenant-scoped identity must be available consistently on SQLite and InnoDB.
- Correct Vehicle Service batch-pricing rollback to specify local FK columns and the explicit index name through Laravel's Fluent command. An ignored second argument to `dropForeign` caused a real InnoDB rollback failure; the corrected rollback was executed successfully.
- Keep supplier balance presentation dependent on a Supplier-owned interface implemented by Invoice. Invoice continues to calculate balances; Supplier does not query Invoice tables or depend on Invoice implementation types. This removes the circular production-module dependency.
- Preserve serial stock's existing receipt-batch relationship in Inventory batch validation. Make the FEFO fixture explicitly batch-tracked rather than weakening stock validation.
- Preserve unknown vehicle odometer as blank/null rather than fabricating zero. Update creation fixtures for explicit generated-code requests and current selectors/navigation.
- Normalize decimal aggregate assertions using Core DecimalMath so SQLite `20` and InnoDB `20.000000` are compared at the same exact scale. No monetary calculation is changed for this test difference.
- Remove the unused line-editor import and format changed PHP files. Historical append-only change records are preserved, including inherited whitespace; they are not rewritten for formatting.

## Executed verification

- Full SQLite backend suite: 881 tests, 9,693 assertions passed.
- Full MariaDB 10.11.7 / InnoDB backend suite: 881 tests, 9,693 assertions passed.
- Frontend: 101 files, 374 tests passed.
- TypeScript typecheck and Vite production build passed.
- ESLint: zero errors; three inherited `react-hooks/set-state-in-effect` warnings remain in the service-history report, quick vehicle modal and employee picker.
- Changed PHP formatting checked with Pint.
- SQLite: clean install, upgrade from the authoritative baseline, rollback/reapply and fresh seeding passed. All 238 table column/FK/index schemas match between fresh and upgraded databases and after rollback/reapply; integrity and foreign-key checks pass.
- InnoDB: clean install, baseline upgrade, rollback/reapply and fresh seeding passed. All 238 table column/key/index schemas match between fresh and upgraded databases and after rollback/reapply.
- Conflict markers and unresolved Git index entries checked; final source diff reviewed.

The scratch-backed InnoDB attempt encountered corrupted tablespaces. A fresh disposable database under local `/tmp` completed the entire suite and schema checks. The server and test processes ran together over loopback TCP; no production database was accessed. MariaDB/InnoDB execution is not represented as Oracle MySQL 8.4 execution or a multi-process contention test.

## Release boundary

GitHub Actions run `36603035572` did not run test steps: its annotation says the account is locked due to a billing issue. No paid service, billing change or CI-disable workaround was used. Local executed results above are the verification evidence.

Publishing this integration to the requested `worktree-0.0.8` and production/default `worktree` branches is a repository release. No connected hosting target, deployment command or production credentials have been established. A live application deployment and smoke test cannot be claimed from a Git merge. Production-data migration rehearsal and human UAT are also distinct from the disposable-database checks above.
