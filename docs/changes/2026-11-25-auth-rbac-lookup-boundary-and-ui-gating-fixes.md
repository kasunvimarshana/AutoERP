# Auth, RBAC Lookup Boundaries, and Access UI Gating Fixes

## Context & Objectives
Follow-up review and remediation of authentication, authorization, and User/Role/Permission CRUD boundaries:
1. Resolved 403 Forbidden failures when users holding `roles.assign_permissions`, `users.assign_permissions`, `users.assign_roles`, or `users.create` loaded required permission, role, or password-policy lookups without holding broad `permissions.view`, `roles.view`, or `users.view` permissions.
2. Closed an inactive-permission leak in `PermissionService::get()` so deactivated permissions return `404 Not Found` consistently with `list()` and `modules()`.
3. Fixed `UserListPage.tsx` and `CreateUserPage.tsx` so organization-unit, role, and password-policy lookup requests are strictly gated on the permissions actually authorized by their backend endpoints.
4. Added backend and frontend regression tests covering these permission boundaries and UI states.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/User/Routes/api.php` | Allowed variadic lookup permissions on `GET /api/v1/users/password-policy`, `GET /api/v1/roles`, `GET /api/v1/permissions`, and `GET /api/v1/permissions/modules`. |
| `app/Modules/User/Http/Requests/AuthorizedUserRequest.php` | Added `requiredPermissions(): array` hook supporting multi-permission authorization checks. |
| `app/Modules/User/Http/Requests/Roles/ListPermissionsRequest.php` | Authorized `permissions.view`, `roles.assign_permissions`, and `users.assign_permissions`. |
| `app/Modules/User/Http/Requests/Roles/ListRolesRequest.php` | Authorized `roles.view` and `users.assign_roles`. |
| `app/Modules/User/Services/PermissionService.php` | Allowed assignment lookup permissions on `list()`/`modules()` and filtered `get()` by `is_active = true`. |
| `app/Modules/User/Services/RoleService.php` | Allowed `users.assign_roles` on `list()` in addition to `roles.view`. |
| `resources/js/modules/access/UserListPage.tsx` | Gated organization-unit filter fetch strictly on `organization_units.view`. |
| `resources/js/modules/access/CreateUserPage.tsx` | Gated role, organization-unit, and password-policy fetches on `canCreate && canManageOrganizationAccess` (and `canAssignRoles` for roles). |
| `tests/Feature/User/UserAccessApiTest.php` | Added assertions for inactive `GET /api/v1/permissions/{id}` `404` and assignment/creation lookup boundaries. |
| `resources/js/modules/access/AccessPages.test.tsx` | Added Vitest cases for `users.view`-only `UserListPage` and `roles.assign_permissions`-only `RoleListPage` / `RoleDetailPage` / `RoleEditPage`. |

## Verification & Quality Gate Results
- `php artisan test tests/Feature/Auth tests/Feature/User tests/Unit/Auth tests/Unit/User tests/Feature/Database/TenantAccessProvisionerTest.php` — **57 passed (431 assertions)**
- `php artisan test --filter "ModuleMigrationBaselineTest|HistoricalRetentionMigrationTest"` — **2 passed (25 assertions)**
- `npm test` — **177 test files passed (745 tests passed)**
- `npm run typecheck` — **0 errors**
- `npm run lint` — **0 errors**
- `php vendor/bin/pint --test` (modified PHP files) — **passed (0 violations)**

## Author & Maintainer Signature
- Lead Architect & Developer: Kasun Vimarshana
- Email: kasunvmail@gmail.com
- Phone: +94713525253
- Repository: https://github.com/kasunvimarshana/AutoERP
