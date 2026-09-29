# Vehicle Rental production release

Date: 2026-09-29
Release candidate branch: `worktree-0.0.8`
Production/default branch: `worktree`
Validated runtime baseline: `2c80584446536fa8b1ebfec4c05adbd129706f45`
Pre-release completion-audit head: `890d14bbacaa39b0e271faa3fa83b08ee596b2e6`

## Release scope

Promote the completed fresh Vehicle Rental implementation and its supporting owner-module fixes/documentation from `worktree-0.0.8` into the repository default production branch `worktree`.

This release does not restore, reuse, copy or depend on removed legacy Rental code. It does not include unrelated open pull-request work.

## Release evidence

The runtime baseline already passed the recorded executable acceptance gates:

- Laravel / SQLite: 820 tests / 8,913 assertions;
- frontend Vitest: 88 files / 327 tests;
- TypeScript typecheck;
- ESLint;
- production Vite build;
- fresh SQLite migration and seed;
- rollback/reapply of the September 25 Rental upgrade migrations with foreign-key integrity clean;
- MySQL grammar compilation of the changed constraint migrations.

Subsequent commits before this release record are documentation-only, so they do not change the tested runtime tree.

The final completion audit found no evidence-backed unfinished Vehicle Rental runtime requirement and no justified schema/relationship rewrite.

## Promotion safety

- `worktree` is the merge base of `worktree-0.0.8`; the release candidate is a forward history of the production/default branch rather than a divergent replacement.
- Open PR #77 (`Dashboard phase2`) targets `worktree` but is unrelated to this Vehicle Rental release and is intentionally excluded.
- Repository CI is configured for pushes/PRs on `worktree-0.0.8`, not `worktree`; this promotion does not require or intentionally invoke GitHub Actions.
- The repository has no existing GitHub Release convention and no repository-owned deployment/cPanel script, so this release does not invent a parallel tag/deployment mechanism.

## Production boundary

For this repository, the production promotion is the audited merge into the default branch `worktree`. Any hosting/runtime deployment outside GitHub must use the existing infrastructure-owned deployment process; no unverified hosting credentials, server path or command is inferred by this release.

## Post-promotion checks

After merge, verify:

1. `worktree` contains this release candidate history;
2. the merged production commit contains the final Vehicle Rental audit and canonical `docs/knowledgebase.md`;
3. PR #77 remains unmerged and separate;
4. no release-time runtime/code/schema changes were introduced after the validated baseline.
