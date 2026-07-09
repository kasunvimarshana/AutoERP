# Production readiness baseline

## Context

The project needed an explicit production gate instead of relying on local development defaults or informal manual steps.

## Change

- Added `composer production:verify` as a single production verification entrypoint.
- Added `.env.production.example` with production-safe defaults for debug mode, cookies, sessions, tenant fallback, logging, database, queue, cache, mail, tenant routing, and private document storage.
- Added `docs/deployment/production-readiness.md` to define environment, verification, migration, permissions, runtime process, storage, smoke testing, and rollback gates.

## Verification

- Confirmed the repository default branch is `worktree`.
- Compared `vehicle-service-lifecycle-boundary-20260709` against `worktree` and confirmed it is ahead and not behind.
- Inspected existing Composer and npm scripts before adding the production verification script.
- Inspected `.env.example` and added a separate production template instead of weakening the local development template.
- No runtime Laravel/MySQL, TypeScript, or production-like UAT suite was available in this connector session.

## Open gate before production

- Run `composer production:verify` in a real runtime environment.
- Run migrations and permission sync against a production-like database.
- Complete production-like smoke tests for auth, tenant scope, key module writes, invoice/payment/posting, document storage, and reports.
