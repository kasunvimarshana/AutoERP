# Organization-unit foundation verification and completion

Date: 2026-06-26

## Why

The initial OrganizationUnit correction established the new hierarchy, authorization, trusted context, lifecycle, private storage, configuration inheritance, membership ownership, and guided frontend. This follow-up completes the remaining regression verification and closes defects discovered while verifying the corrected source.

## Additional corrections

- Fixed tenant storage cleanup claim execution so the captured claim timestamp is always available inside the transactional closure.
- Changed logo replacement to a POST request with an explicit PUT method override, ensuring PHP/Laravel reliably parses multipart uploads.
- Replaced `FOR UPDATE` aggregate-count patterns with locked row selection before counting for portable MySQL/PostgreSQL locking behavior.
- Removed redundant tenant-ownership model traits where the base model already owns the invariant.
- Registered the OrganizationUnit management route as a tenant-administration capability that requires permission but not an already selected OU.
- Extended global configuration impact preview with active OU population, direct OU override count, and inherited-chain count.
- Added an OrganizationUnit-owned population reader while keeping configuration value counting inside Configuration.
- Locked active organization units while User creates or changes memberships, preventing assignment from racing with OU deactivation or retirement.
- Replaced user-organization status literals in migrations with the owning status constants.
- Removed the remaining legacy browser OU key during session commit/clear; current OU is server/session owned only.
- Documented exact-membership semantics, explicit-null configuration semantics, and owner-provided migrations for incompatible definition versions.

## Verification completed

- Full TypeScript semantic check.
- Full frontend ESLint.
- Full frontend Vitest suite.
- Production Vite build.
- Full project PHP syntax lint excluding dependencies.
- Internal PHP symbol/import scan.
- Route controller/action source scan.
- Migration ownership/table-creation scan.
- Legacy OrganizationUnit API/symbol/reference scan.
- Source ZIP integrity and SHA-256 generation.

## Remaining runtime release gates

The source snapshot does not include Composer dependencies or a migrated database runtime. Laravel boot, `route:list`, `migrate:fresh`, PHPUnit, real database concurrency tests, queue/storage integration, and browser/API OU-A versus OU-B adversarial E2E remain deployment gates. The rewritten create migrations require a fresh development database or a separately reviewed production data-migration plan.
