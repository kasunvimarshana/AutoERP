# Local integration and governed Rental proofs — 2026-10-31

## Scope and source

This candidate integrates the published verification checkpoint `4427d522a36a2cd713ee17aaff346ff3287f4bed`, Selling `abd3da225062c392add402aec93380f576154677`, and Consumables/customer ownership `720c51df5fc409c288d5a51079494343cf3a9753`. Twenty Selling conflicts and one subsequent navigation conflict were resolved. The target remains `worktree-0.0.8` at `0b28bb6372d5ada787a514e020080aa2cb8c2fc8`; PR #127 remains at `cc6c884e1660254db3064f04837c7861f54a2eb0`.

Publication is on `local-integration/release-20261031`, outside the existing workflow push filters, with no new pull request. No Actions runs/retries, paid services, fabricated check statuses, approval bypass, production deployment or production financial changes were used. Publishing onto PR #127 or the target would trigger the prohibited workflow. This record's results apply to this new candidate, not to the unchanged PR head.

All 593 pre-publication remote branch tips were inventoried: 501 are ancestors of this integration candidate and 92 are other historical tips. Ancestry is not semantic approval of every historical branch; those other tips were not blindly merged. Only PR #127 and issues #124/#128 are open.

## Changes and reasons

- Integrated the current Selling stock/consumable purchase policy, credit settlement and reporting changes, followed by consumable consumption/reversal/reporting, service exports and customer telephone ownership. Preserved prior Rental controls, currency rendering, stale-response protection and six-method Vehicle Service payment handling.
- Moved new customer-outstanding reporting helpers to Reporting and corrected its API route/locations route. Moved Selling payment finalization and its controller/route to Selling, eliminating the Payment-to-Selling dependency cycle while retaining Payment-owned posting and permission/feature guards.
- Consolidated cash/card fields into the existing Payment validation and frontend payload helpers, retaining instrument references. Fast Purchase preview and posting now share its stock/consumable purchase validation. Updated conflicting old test fixtures to the explicit stock-only policy without removing conservation assertions; direct non-stock/mixed purchases must fail without financial/inventory side effects.
- Prevented Vehicle Service credit resubmission and froze its invoice/mode during posting. Added the pending-credit UI regression. Corrected report contract/date fixtures, FX snapshots and customer phone/job type fixtures to the integrated requirements.
- Kept Purchase cancellation and Customer row-version alterations in their owners' upgrade migration directories. Corrected the cancellation foreign-key rollback to use explicit columns with the existing index name, allowing SQLite and MySQL rollback/reapply. The first SQLite rollback exposed this portability failure.
- Added governed Rental incident file proofs: private PDF/JPEG/PNG objects up to 10 MB, immutable metadata/checksum/actor/time, tenant-composite references, scoped authenticated download, no internal object-key disclosure, expected-version upload and factual-review locking. Duplicate/stale uploads fail; failed database/audit writes clean up unreferenced files. Uploads advance the incident version and audit history without creating invoices, payments or journals.
- Added API/service and frontend coverage for proof upload/download, invalid/oversize content, duplicate/stale writes, reviewed locking, tenant/organization/permission boundaries, immutable history and transactional cleanup. The UI refreshes versioned evidence and review state after upload. Only the governed-file-proof subtask is marked complete in Rental TODO §R.
- Corrected React compiler callback dependencies and an unused import. The only failure in the first full MySQL suite was an exact-string quantity assertion (`2` versus `2.000000`); it now compares the six-decimal numeric value using BCMath. The same final test passes on both engines.
- Updated the three vulnerable Symfony packages (http-foundation, mime, yaml) to 7.4.22. Refreshed compatible npm packages, including Vitest 4.1.12 and ESLint 10.14.0. The remaining minimatch advisory required an explicit `^11.0.3` override because the lint parents still require vulnerable major 10. Inspected their `Minimatch`/`minimatch` API usage and verified the override through clean install, typecheck, lint, tests and build; no forced downgrades or audit exclusions were used. Both final dependency audits report zero vulnerabilities.

## Actual local verification

Runtime: PHP 8.3.6, SQLite, disposable MySQL Community 8.4.11, Node 24.22.1, npm 11.9.0. MySQL ran only on local TCP, with its Unix socket disabled because this runtime does not support Unix sockets. No production connection or historical financial dataset was used.

| Check | Result |
| --- | --- |
| Full PHPUnit SQLite | 957 tests / 10,622 assertions passed |
| Full PHPUnit MySQL | 957 tests / 10,622 assertions passed |
| Final Vitest, patched lockfile | 129 files / 468 tests passed; JSON report confirms success |
| TypeScript / ESLint / Vite production build | Passed |
| Clean npm install and audit | Passed; zero vulnerabilities at all severities |
| Composer strict validation / platform requirements / locked audit | Passed; no advisories or abandoned packages |
| Python read-only audit regression | 5 passed |
| Changed PHP syntax | 151 files passed |
| New proof files and final focused PHP formatting | Passed |
| SQLite fresh / full rollback / reapply | Passed |
| MySQL contention / populated currency upgrade / five audit SELECTs | 3 tests / 46 assertions passed |
| MySQL fresh / full rollback / reapply | Passed |

The final full backend suites include the revised decimal assertion and all application changes. The first frontend run after dependency updates exited successfully but omitted the dot reporter's footer; a second run with JSON output verified all 468 tests. Broad dependency updates were followed by a fresh locked installation and the complete frontend validation. Test and migration failures encountered during integration were corrected and rerun; earlier failing logs are not claimed as passes.

## Free reproduction

Install the locked dependencies locally. Use PHP 8.3 with BCMath, PDO SQLite/MySQL, DOM/XML, mbstring, fileinfo and the other Composer-required extensions. Run each destructive database command only against a disposable local test database; the explicit MySQL bootstrap requires a database name ending `_test` and an empty `DB_URL`.

```sh
composer install --no-interaction
npm ci --ignore-scripts
composer validate --strict
composer check-platform-reqs
composer security:audit
npm audit
php vendor/bin/phpunit
# Set APP_ENV=testing, a test-only APP_KEY, DB_CONNECTION=mysql, local DB_HOST/DB_PORT,
# disposable DB_DATABASE ending _test, test credentials, DB_URL= first:
php vendor/bin/phpunit --configuration phpunit.mysql.xml
php vendor/bin/phpunit --configuration phpunit.mysql.xml tests/Database/PurchaseReleaseVerificationTest.php
python -B -m unittest discover -s docs/audits -p 'test_*.py' -v
npm run typecheck
npm run lint
npm test -- --reporter=json --outputFile=frontend-results.json
npm run build
```

The standalone MySQL migration harness initially lacked APP_KEY and stopped; setting an explicit test-only key corrected that environment error. For each engine, use a separate disposable migration database and run `php artisan migrate:fresh --force --no-interaction`, `php artisan migrate:rollback --step=10000 --force --no-interaction`, and `php artisan migrate --force --no-interaction`. Never run these commands against the historical dataset intended for read-only reconciliation.

## Exact remaining blockers

- PR #127 is draft; its only review is COMMENTED and expressly NOT APPROVED. There is no approving review and no eligible approved PR to merge. No inline review comments were returned. Genuine independent review remains required.
- The current PR head still has three completed failed check runs: Backend SQLite, Backend MySQL and Frontend. Issue #124 remains open. Branch-protection reads return HTTP 403 with this integration, so requirements cannot be inferred away. An administrator must establish an authorized non-Actions verification mechanism that satisfies the real mandatory policy without weakening it. Local results do not impersonate GitHub checks; a self-hosted Actions runner would still violate the user's restriction.
- Issue #128 needs authorized historical source-currency evidence, duplicate/allocation review and Invoice/Finance/GL reconciliation. The passing audit and populated currency-upgrade tests use synthetic fixtures, preserve historical monetary fields and leave unproven currency NULL; they do not establish real accounting tie-out or authorize repair of production money.
- All nine aggregate Rental §R financial gates remain open: commercial liability decisions/splits/disputes; customer/owner adjustment documents and immutable tax snapshots; Finance posting/FX/period/tax/reversals across every note path; historical currency reconciliation; complete approved Payment scenarios; source-document-settlement reporting; broader populated upgrades/concurrency/authorization, financial UAT and deployment smoke. The completed private proof slice does not complete those financial workflows. Commercial policy and Finance/business acceptance cannot be invented.
- The populated test covers one debit-note currency upgrade and two Purchase allocation contention scenarios, not every populated migration or concurrent financial path. No production deployment/smoke or historical reconciliation was performed.

Available free next steps are independent code review, the local commands above, read-only reconciliation against an authorized isolated restored dataset, approved Finance/business scenarios and UAT, and an administrator-supported non-Actions check mechanism. Keep PR #127 and issues #124/#128 open until their actual requirements are met. This candidate is not declared production-ready.
