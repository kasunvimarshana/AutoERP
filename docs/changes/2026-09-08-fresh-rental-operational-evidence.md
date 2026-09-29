# Fresh Rental vehicle use and Running Charts — 2026-09-08

Baseline: `worktree-0.0.8` at `55166a8506303f7f40d149b31ab119682aa118b5`.

## Purpose and changes

The fresh agreement foundation could capture commercial context but could not record actual vehicle use. Implement new bounded planning, handover, return and cancellation; Running Chart draft editing, finalization, reversal and correction lineage; APIs, guided UI, permissions and immutable history. This code was written fresh without restoring removed Rental files or classes.

The exact source-derived versus integrity-derived boundaries, relationship rationale, states, API and remaining scope are in `docs/vehicle-rental/operations.md`; `docs/knowledgebase.md` and the prioritized TODO now reflect the delivered slice. Customer and owner histories remain independent; Running Charts reference vehicle use and its immutable revision instead of duplicating vehicle/customer/owner identities. No financial formula, tax default or artificial owner payable is introduced.

Vehicle owns company-ownership coverage, status transitions and shared availability. Rental publishes its own occupancy blocker. Vehicle Service now checks shared admission before Inspected/InProgress transitions and admitted job period changes; physical vehicle changes require the job lifecycle to be corrected first. Ownership commands acquire Vehicle before ownership rows, matching availability writes. Integrity checks use locking reads after the vehicle mutex to avoid relying on stale transaction snapshots. Owner agreement identity validation now correctly accepts vehicles belonging to the selected organization as well as tenant-shared vehicles.

A generic immutable-history base replaces the agreement-specific class name; existing table identity/history remains intact. New tables use explicit migrations and tenant-safe identity/revision foreign keys. No existing relationship is removed blindly.

The full frontend run exposed a pre-existing timezone-dependent Tenant subscription test: its clock used UTC while its inputs represented local time. Align the test clock with its local inputs. No subscription business behavior changes and no Rental workaround is added.

## Source coverage and recovery

The earlier interval-frame survey and selected anchors are not a continuous audiovisual audit; narration and dedicated rental data remain incompletely inspected. In the authorized backup recovery attempt, source configuration/strings were checked and five distinct exact printable password-field values were tested using free 7-Zip. None opened the protected backup. No password values, guessed variants or brute-force attack are included in the repository. The backup remains uninspected; this did not stop the nonfinancial implementation.

Some uncommitted work disappeared when the scratch environment resumed during this session. Running Chart code was recreated fresh and verified against the files actually present; earlier lost-file test counts are not claimed for this commit.

## Verification on the actual delivered files

- Complete PHP/SQLite suite: 688 tests, 7,841 assertions passed.
- After adding explicit organization-owned vehicle and independent chart permission regressions: Rental suite 21 tests, 135 assertions passed. The two extra tests were not part of the preceding full-suite count.
- Complete Vitest rerun: 79 files, 293 tests passed, including operational UI, unknown distance, version conflicts, reversal reasons and second-preserving timestamps.
- TypeScript, changed Rental ESLint, PHP Pint, production build and whitespace validation passed.
- All checks used free local tools; no GitHub Actions or production deployment was run.

These checks do not establish InnoDB contention, production migration safety, authenticated browser/UAT, complete audiovisual coverage or financial posting correctness. This operational slice is not the complete production Rental module. Open-ended plans, atomic replacements, HR/external driver identity, successor commercial terms, calculations, Invoice/AP/Payment/Tax/Finance integrations, deposits and release acceptance remain outstanding in the TODO.
