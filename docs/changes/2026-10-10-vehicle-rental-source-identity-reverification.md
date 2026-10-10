# Vehicle Rental — 2026-10-10 source identity reverification

## Scope and authority

Review the uploaded TACGL archives and four videos against the canonical Vehicle Rental evidence register, the latest `worktree-0.0.8` implementation, `docs/knowledgebase.md`, and `docs/vehicle-rental/TODO.md`. Preserve the existing fresh implementation and module ownership. Starting authoritative branch head: `e1f68d98d341629185c6d8afa22a23751b4ac5d8`.

## Evidence executed in this review

- `TACGL.zip`: SHA-256 `0e0733fff720072af4c3aaa787995ff128bfa79060a37739d6d2ebbe18a25313`; 452 files.
- `TACGL(20261010-112139).zip`: SHA-256 `79c240494943437978754169c3360bb7c6e35d911ef8263c4b2d6b6246384d77`; 452 files.
- After removing the canonical archive's outer `TACGL/` prefix, normalized paths, uncompressed sizes and ZIP CRC-32 values match for all 452 files; neither archive has encrypted ZIP entries. The dated ZIP is byte-identical by SHA-256 to the previous dated package described in the canonical knowledge base.
- `TACGL.rar`: SHA-256 `0001a91ab4dfdf31d60f669b30f1ccc2da475d7841b2ceea505ef97eff5bedbf`.
- Four videos' SHA-256 values match the canonical knowledge-base register: `1.mp4`, `Recording 2026-06-21 132314.mp4`, `2.mp4` and `ScreenVideo_03-04-2026_18-02-52.mp4`.
- The nested `DATABACKUP/!   CTACGLDATABACKUP202503271759.rar` was inspected non-destructively with free RAR header tooling: 86 named members, all requiring a password; no archive comment. No explicit backup password was obtained. No brute force, credential guessing or application-password reuse was attempted.
- Connected GitHub check: no open pull requests and no open issues matching the Vehicle Rental scope at the starting head. The existing implementation acceptance ledger contains no unchecked items.

This is a source-identity check and targeted repository inspection, not a newly executed continuous playback audit or an independent reexecution of all previous business observations.

## Source-to-runtime and relationship decision

The latest uploads contain no newly accessible distinct business payload. The existing canonical domain invariants remain unchanged: independent Customer/Owner agreements and calculations, shared Running Chart physical evidence, one-sided duplicate-consumption prevention, effective vehicle-use/custody histories, and owner-module Invoice/Payment/Tax/Finance handoff.

The existing current-head source uses directional agreement successor, Vehicle Use replacement and Running Chart history lineages. The targeted review did not establish a new relationship defect or justify modifying an already accepted relationship. No legacy Rental code is restored, no compatibility adapter is introduced and no new commercial amount, threshold, tax rate or surcharge is invented.

## Verification and release boundary

The execution container cannot resolve `github.com` using `git ls-remote`, and the repository's Composer/npm dependency tree is not present locally. Consequently **no current-head full Laravel, MySQL/MariaDB, migration, Vitest, typecheck, ESLint, build or live-deployment pass is claimed**. Historical executed suites are recorded in previous change records and must not be represented as a new current-head run.

The evidence correction is documentation-only. No runtime, schema, API, UI, test or financial rule was changed, so this change introduces no runtime behavior to test. There is no basis for a new production code patch solely because the same TACGL corpus was uploaded again.

## Changes

- Reconcile the actual 2026-10-10 TACGL package identity in `docs/knowledgebase.md` without removing the earlier source provenance.
- Append this change record; leave all earlier `docs/changes` records unchanged.
- Do not declare a new live production deployment, backup recovery or full test-suite execution from a documentation-only change.
