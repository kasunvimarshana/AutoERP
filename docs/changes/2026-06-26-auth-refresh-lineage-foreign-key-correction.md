# Auth refresh lineage foreign-key correction

## Context

MySQL rejected creation of `auth_refresh_tokens` with errno 150. The tenant refresh-token lineage used a composite foreign key from `(parent_refresh_token_id, tenant_id)` to `(id, tenant_id)` with `ON DELETE SET NULL`.

## Root cause

For a composite `SET NULL` foreign key, MySQL must be able to set every child column in the constraint to `NULL`. `tenant_id` is intentionally mandatory, so the constraint was not a legal MySQL foreign key.

## Change

- Changed `auth_refresh_parent_fk` to `ON DELETE RESTRICT` in the owning create migration.
- Kept the composite tenant binding so a refresh token cannot reference a parent from another tenant.
- Updated Auth retention to purge tenant refresh-token lineage leaf-first in bounded batches. Parents are deleted only after their retained children are gone.
- Did not add a patch migration or weaken tenant ownership.

## Verification

- Auth migration and retention service pass PHP syntax checks.
- Static foreign-key validation reports no composite `SET NULL` constraint containing a non-nullable child column.
- The source package must still be run through `php artisan migrate:fresh --seed` on the target MySQL runtime for complete runtime verification.
