# Dynamic User-Configurable Quick Access (Database + LocalStorage Hybrid)

**Date:** 2026-11-19  
**Modules:** User, Settings, Layout Navigation

Implemented the end-to-end user-configurable Quick Access feature using a Database + LocalStorage hybrid approach, persisting user-specific preferences in the database as the single source of truth while leveraging LocalStorage for instantaneous, zero-flicker client-side caching and reactive cross-tab updates.

### Summary of Changes

1. **Database Schema & Persistence (`app/Modules/User/Database/Migrations/2026_06_12_030013_create_user_quick_access_settings_table.php`)**:
   - Created `user_quick_access_settings` table strictly scoped by `tenant_id` and `user_id`.
   - Adhered to repository one-table-per-file migration baseline and tenant isolation rules with:
     - Composite unique candidate key `['id', 'tenant_id']`
     - Composite user-tenant foreign key `['user_id', 'tenant_id']` referencing `users(id, tenant_id)` with `cascadeOnDelete()`
     - Unique constraint `['tenant_id', 'user_id']` guaranteeing single configuration per user
     - Optimistic concurrency control via `row_version` column.

2. **Domain Model & Service (`UserQuickAccessModel.php`, `UserQuickAccessService.php`)**:
   - Built `UserQuickAccessModel` extending `TenantOwnedModel` with automatic `HasTenantScope` enforcement.
   - Built `UserQuickAccessService` providing:
     - `get($tenantId, $userId)`: returns user's persisted preferences or sensible defaults if not yet customized.
     - `update($tenantId, $userId, $rawItems, $expectedVersion)`: normalizes and sanitizes route paths and labels, performs optimistic locking via `row_version` check with conflict rejection (`UserErrorCode::STALE_RECORD`), and atomically updates or creates records.
     - `reset($tenantId, $userId)`: removes custom record, restoring defaults cleanly.

3. **Backend API Endpoints (`UserQuickAccessController.php`, `UpdateUserQuickAccessRequest.php`, `api.php`)**:
   - Registered endpoints under `/api/v1/user/quick-access`:
     - `GET /api/v1/user/quick-access`: fetch current user's quick access configuration.
     - `PUT /api/v1/user/quick-access`: update current user's shortcuts and display order.
     - `DELETE /api/v1/user/quick-access`: reset shortcuts to system defaults.
   - Guarded by tenant user authentication and user record context resolution (`current.user-record`).
   - Validated against unauthorized parameter tampering by prohibiting `tenant_id` and `user_id` in request payloads, taking them exclusively from cryptographically verified token attributes.

4. **Dynamic Preset Registry & Caching (`quickAccessService.ts`, `quickAccessApi.ts`)**:
   - Replaced hardcoded shortcut presets with dynamic `getAvailablePresets(sections)` extractor that traverses `tenantNavigationSections` and custom operational routes, automatically supporting routes and actions from existing and future modules.
   - Implemented tenant-and-user-scoped LocalStorage cache keys (`autoerp.quick_access_items.${tenantId}.${userId}`) to prevent cache leakage across users or tenants sharing a workstation.
   - Enhanced `useQuickAccessConfig` hook with hybrid lifecycle:
     - Immediate zero-latency render from LocalStorage cache.
     - Background sync from database single source of truth.
     - Optimistic local updates for zero-lag dropdown response without page reloads.
     - Server persistence with `row_version` conflict handling (automatic refresh on 409).

5. **Settings Management UI with Drag-and-Drop (`QuickAccessSettingsPage.tsx`)**:
   - Implemented native HTML5 drag-and-drop reordering with visual drag handles and drop indicators.
   - Retained accessible Move Up / Move Down buttons for screen readers and keyboard accessibility.
   - Integrated live dropdown preview displaying only permitted and enabled shortcuts.
   - Added workspace permission boundary indicators highlighting restricted routes when a user lacks module or role permissions.
   - Connected preset auto-fill selector to dynamic registered module presets.

6. **Header Quick Access Dropdown (`QuickAccessMenu.tsx`)**:
   - Subscribed to hybrid cache updates and scoped storage keys.
   - Maintained permissions enforcement so only accessible routes in current user workspace sections render.

### Verification
- **PHPUnit Feature Tests (`tests/Feature/User/UserQuickAccessApiTest.php`)**:
  - 7/7 tests passed: default resolution, database persistence, optimistic concurrency (409 on stale version), reset to defaults, tenant & user isolation, route validation, unauthenticated rejection.
- **Architectural Tests (`tests/Unit/Database/ModuleMigrationBaselineTest.php`, `tests/Unit/Database/TenantIsolationArchitectureTest.php`)**:
  - 9/9 passed: verified one-table-per-migration baseline, composite keys, `HasTenantScope` model binding, and request boundaries.
- **Vitest Frontend Suite (`quickAccessService.test.ts`, `QuickAccessSettingsPage.test.tsx`, `QuickAccessMenu.test.tsx`, `navigationUtils.test.ts`)**:
  - 51/51 tests passed: verified LocalStorage caching, tenant/user key isolation, dynamic preset extraction, drag-and-drop reordering, visibility toggling, modal validation, and permissions filtering.
- **TypeScript Typecheck (`npm run typecheck`)**: Passed with 0 errors.
- **Vite Production Build (`npm run build`)**: Compiled successfully in 10.38s.
- **Git Diff Hygiene (`git diff --check`)**: Passed cleanly without formatting issues.
