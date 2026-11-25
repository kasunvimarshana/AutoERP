# Auth, RBAC, Super Admin & Permission Synchronization Audit

**Date:** 2026-11-25  
**Scope:** Authentication, Authorization, Access Control, Super Admin Unrestricted Access, Module Permission Catalogue Synchronization, and User/Role/Permission CRUD

---

## Context & Objectives

Conducted an end-to-end audit of authentication, authorization, access control, and the User, Role, and Permission CRUD modules across backend (`app/Modules/Auth`, `app/Modules/User`) and frontend (`resources/js/modules/auth`, `resources/js/modules/access`):

1. **Super Admin Unrestricted Access Gap:** `UserAccessResolver::can()` previously checked only the tenant's `permissions` database table via `activePermissionCatalogue()`. When a new module or permission was added to the codebase before `TenantAccessProvisioner::provision()` ran for an existing tenant, Super Admin was denied (`403`) on the backend while the frontend (`accessControl.ts`) granted access.
2. **Module Addition / Modification / Removal Synchronization Defects:**
   - `PermissionModel::updating()` threw `LogicException` if `module` changed during catalogue synchronization, crashing `TenantAccessProvisioner::provision()` when permissions moved between modules.
   - `TenantAccessProvisioner::provision()` deactivated removed permissions but left dangling rows in `role_permissions` (for custom roles) and `user_permissions` (for direct user permissions), and did not invalidate `UserAccessResolver` in-memory caches.
   - No Artisan command existed for tenant permission catalogue synchronization (`tenant:permissions:sync`).
3. **Inactive Permission Leakage & CRUD Inconsistencies:**
   - `RoleService` and `UserReadService` returned inactive permissions in role/user payloads and `permissions_count`, causing subsequent role/user permission syncs to fail.
   - `RolePermissionService::sync()` returned `422` instead of `409 Conflict` when attempting to mutate system role permissions.
   - `UserAccountService::create()` and `updateProfile()` did not guard against whitespace-only `first_name` in direct service calls.
   - Frontend `RoleListPage.tsx` and `RoleDetailPage.tsx` hid the Edit button when a user had `roles.assign_permissions` without `roles.update`, and `RoleEditPage.tsx` did not track `permissionsDirty` for button disabling / unsaved-change warnings.

---

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/User/Models/PermissionModel.php` | Added `allowingCatalogueSync()` scope so `TenantAccessProvisioner` can update `module` during catalogue synchronization while keeping `module` immutable outside sync. |
| `app/Modules/User/Services/Provisioning/TenantAccessProvisioner.php` | Wrapped `provision()` in `PermissionModel::allowingCatalogueSync()`, handled empty `$permissionIds` deactivation, cleaned up dangling `role_permissions` and `user_permissions` for inactive permissions, and invalidated `UserAccessResolver` tenant caches. |
| `app/Modules/User/Services/UserAccessResolver.php` | Granted active Super Admin unrestricted access in `can()` and merged `PermissionDefinitionRegistryInterface` definitions into `effectivePermissionNames()` / `effectivePermissions()`. |
| `app/Modules/User/Services/RoleService.php` | Filtered `permissions` relation and `permissions_count` to `is_active = true`. |
| `app/Modules/User/Services/UserReadService.php` | Filtered `direct_permissions` relation to `is_active = true`. |
| `app/Modules/User/Services/AbstractUserCrudService.php` | Mapped system role permission mutation error to `UserErrorCode::CONFLICT` (`409`). |
| `app/Modules/User/Services/UserAccountService.php` | Enforced non-empty `first_name` in `create()` and `updateProfile()`. |
| `app/Modules/User/Console/Commands/SyncTenantPermissionCatalogCommand.php` | Added `tenant:permissions:sync {--tenant=}` Artisan command (services injected in `handle()`). |
| `app/Modules/User/Providers/UserServiceProvider.php` | Registered `SyncTenantPermissionCatalogCommand`. |
| `resources/js/modules/access/RoleListPage.tsx` | Enabled Edit link for `canUpdate \|\| canAssignPermissions`. |
| `resources/js/modules/access/RoleDetailPage.tsx` | Enabled Edit link for `canUpdate \|\| canAssignPermissions`. |
| `resources/js/modules/access/RoleEditPage.tsx` | Added `permissionsDirty` tracking for Save button state and unsaved-changes guard. |
| `resources/js/modules/access/UserListPage.tsx` | Gated `roles` and `organizationUnits` filter fetches on view/assignment permissions. |
| `tests/Feature/Database/TenantAccessProvisionerTest.php` | Added tests for module refactoring, removed-permission cleanup across custom roles/users, and `tenant:permissions:sync`. |
| `tests/Feature/User/UserAccessApiTest.php` | Added tests for Super Admin unrestricted access prior to DB sync, `409` on system role permission sync, and inactive permission exclusion. |

---

## Verification & Quality Gate Results

- **Backend Auth, User, & Provisioner Tests:** `php artisan test tests/Feature/Auth tests/Feature/User tests/Unit/Auth tests/Unit/User tests/Feature/Database/TenantAccessProvisionerTest.php` — **56 passed (421 assertions)**
- **Backend Code Style (Pint):** `php vendor/bin/pint --test ...` — **Passed (0 violations)**
- **Frontend Auth/Access Tests:** `npx vitest run resources/js/modules/auth resources/js/modules/access resources/js/app/access` — **10 test files passed (50 tests)**
- **TypeScript Typecheck:** `npm run typecheck` — **Passed (0 errors)**
- **ESLint Analysis:** `npm run lint` — **Passed (0 errors)**

---

## Author & Maintainer Signature

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
