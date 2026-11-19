# Change Record: Quick Access Review & Architectural Hardening

**Date:** 2026-11-19  
**Scope:** `Modules/User`, `resources/js/modules/settings`, `resources/js/app/layout`  
**Purpose:** Eliminate stale-cache discrepancies, fix cross-tenant cache fallbacks, harden optimistic concurrency control, enforce URL route validation against protocol-relative redirects, and support background synchronization in the header dropdown.

## Summary of Changes

1. **Header Dropdown Background Synchronization**:
   - `QuickAccessMenu.tsx`: Updated the top navigation menu to perform non-blocking background synchronization with `/api/v1/user/quick-access` when authenticated (`auth?.tenant?.id && auth?.user?.id`). This guarantees that multi-device preference updates or fresh browser sessions synchronize with the database single source of truth without requiring the user to navigate to `/settings/quick-access`.
   - `QuickAccessMenuSync.test.tsx`: Created a dedicated Vitest suite verifying stale-while-revalidate background synchronization, local caching, and dynamic menu re-rendering.

2. **Tenant Cache Isolation Hardening**:
   - `quickAccessService.ts`: Removed the cross-tenant fallback to the unscoped global LocalStorage key `autoerp.quick_access_items` when a tenant/user-scoped key was null. Strict tenant isolation requires that each tenant/user session reads only their scoped key or sensible defaults.

3. **Empty List Representation Bug Fix**:
   - `quickAccessService.ts`: Refactored `parseRawItems()` so that intentionally empty shortcut lists (`[]`) are preserved and not replaced with default shortcuts upon background server revalidation. Only unparseable/corrupted inputs fall back to defaults.

4. **Optimistic Concurrency Control Hardening**:
   - `UserQuickAccessService.php`: Fixed optimistic concurrency checks to require matching version numbers when updating existing records. Rejects requests with missing or null versions on existing records with 409 Conflict (`USER_STALE_RECORD`), and rejects expected versions when records do not exist. Wrapped initial inserts in exception handling to catch race conditions without crashing.

5. **Route Validation & Sanitization**:
   - `UpdateUserQuickAccessRequest.php` & `QuickAccessSettingsPage.tsx`: Replaced permissive `starts_with:/` with strict regex `/^\/(?!\/|\\\\)/` preventing protocol-relative URLs (`//evil.com`) or backslash path injection (`/\evil.com`).
   - `UserQuickAccessService.php`: Normalized and deduplicated route paths to guarantee unique routing per user configuration.

6. **Preset Accessibility Filtering**:
   - `quickAccessService.ts`: Deep-link presets with query parameters are now validated against permitted sections before inclusion, ensuring inaccessible operational modules are never exposed in preset menus.

7. **Active Route Parameter Matching**:
   - `QuickAccessMenu.tsx`: Fixed active item highlighting so parameterized shortcuts (e.g. `?view=service`) are not falsely highlighted when visiting different queries on the same pathname.
