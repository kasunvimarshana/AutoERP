# Integration candidate: Tenant private-upload filename hardening — 2026-10-31

## Verified ancestry and exact commits

Fast-forwarded `local-integration/release-20261031` from `9d3074526d16046f5384897988c33f819c145d43` to `64c6e1b9a364186b140fcf46a8fe8ee7f5616bf2`, which is also the tip of `fix/tenant-private-upload-filenames-20261031`. Before moving the ref, GitHub reported **ahead five, behind zero**; the changed file set was exactly the Tenant-owned file-storage service, its focused feature regression test, and the dated audit record. The update used a **non-force, expected-SHA-guarded fast-forward**.

## Code scope

The root cause, change and test inputs are described in `2026-10-31-tenant-private-upload-filename-normalization.md`. User-controlled upload filenames from Windows or POSIX directories are reduced to a leaf name, control characters are removed, and empty/dot-only names are replaced by the existing fallback. No tenant object paths, financial records, application database schema, authorization rules, or incident liability calculations changed.

## Verification and limits

- A local PHP 8.4.24 standalone check for the **changed filename normalization** passed **5/5 cases**; the standalone script passed `php -l`; PDF fixture detection returned `application/pdf`.
- The available container lacks `mbstring`, PDO SQLite/MySQL drivers, Composer and a full repository clone. The unchanged `mb_substr` call could not be executed there. The new Laravel test and the full backend/frontend/upgrade/security suites were **not rerun** on this updated head.
- Historical integration candidate `383d49456630195e849f1821492b8f00e1079b7c` has separately recorded passing local suites, but **those outcomes must not be presented as tests of this head**.
- The candidate branch is outside existing GitHub Actions push filters and has no PR. No Actions execution/retry, paid service, target-branch update, required-check bypass, approval fabrication, or deployment took place.

## Release boundary

The authoritative target `worktree-0.0.8` and draft PR #127 were not moved. Open #124 and #128 remain legitimate blockers. Rental TODO §R still has nine financial acceptance gates, including complete Invoice/Finance/GL reconciliation with authorized historical evidence and business/UAT approval. This integration is a **development candidate**, not a production release.
