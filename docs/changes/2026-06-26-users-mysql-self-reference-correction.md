# Users MySQL self-reference correction

## Context

MySQL rejected creation of the `users` table with `errno: 150`. The base user aggregate contained three tenant-qualified self-referencing foreign keys for `created_by_user_id`, `updated_by_user_id`, and `deleted_by_user_id`.

## Change

- Removed the circular `users -> users` foreign-key constraints from the original `users` create migration.
- Preserved `created_by_user_id`, `updated_by_user_id`, and `deleted_by_user_id` as nullable historical actor identifiers.
- Added tenant-qualified indexes for actor lookup performance.
- Kept the authoritative `users.tenant_id -> tenants.id` foreign key and all business relationships unchanged.

## Reason

Audit actor identifiers are historical attribution fields, not aggregate ownership relationships. A base aggregate should not depend on itself for schema creation or deletion. Immutable audit events remain the authoritative audit history, while the indexed actor identifiers provide convenient attribution without circular database coupling.

## Verification

- Updated migration passes PHP syntax validation.
- The `users` create migration no longer declares self-referencing foreign keys.
- Tenant ownership and composite user identity indexes remain intact.
