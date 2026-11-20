# Change Record: Quick Access Skeptical Review & Hardening

**Date:** 2026-11-20  
**Scope:** `Modules/User`, `resources/js/modules/settings`, `resources/js/app/layout`  
**Purpose:** Resolve inaccessible module leakage in query-parameterized shortcuts, eliminate duplicate item ID collisions and paired mutations, harden optimistic cache synchronization, and enforce backend item ID disambiguation.

## Summary of Changes

1. **Inaccessible Route Query Leakage Elimination (`quickAccessService.ts`)**:
   - Resolved a critical accessibility leakage bug where shortcuts targeting query-parameterized module views (e.g., `/invoices?view=service`, `/payments?view=service`, `/inventory?tab=adjustments`) were falsely marked as accessible when their parent base route (e.g., `/invoices`, `/payments`, `/inventory`) was enabled, even though the specialized submodule (e.g., Vehicle Service) was disabled in the tenant's workspace.
   - Enhanced `isQuickAccessItemAccessible()` to check whether the system defines a registered navigation item matching the shortcut's ID or exact path with queries in `tenantWorkspaceNavigationSections`. If a registered system link exists but is absent from the user's filtered `sections`, it is strictly rejected as restricted.

2. **Guaranteed Unique Item IDs & Disambiguation (`QuickAccessSettingsPage.tsx` & `UserQuickAccessService.php`)**:
   - Fixed a bug where selecting an existing preset and modifying its route created duplicate shortcut IDs, causing React duplicate key reconciliation warnings (`key={item.id}`) and paired mutation bugs (where toggling or deleting one shortcut mutated both).
   - In `QuickAccessSettingsPage.tsx`, `handleSaveForm` now detects duplicate IDs and appends a unique timestamp suffix.
   - In `UserQuickAccessService.php`, `update()` now disambiguates duplicate item IDs in request payloads (`$seenIds`), guaranteeing unique IDs across database records.

3. **Explicit State Setting on Server Confirmation (`quickAccessService.ts`)**:
   - In `updateItems`, `setItems(updated)` is now explicitly invoked with the server's confirmed items rather than relying solely on the DOM event loop side-effects of `saveQuickAccessItems`.

4. **Comprehensive Test Coverage Expansion (`quickAccessService.test.ts`, `QuickAccessSettingsPage.test.tsx`, `UserQuickAccessApiTest.php`)**:
   - Added unit test in `quickAccessService.test.ts` verifying that `/invoices?view=service` is strictly rejected when Vehicle Service is omitted from active workspace sections, even though Billing `/invoices` is active.
   - Added unit test in `QuickAccessSettingsPage.test.tsx` verifying unique ID assignment and persistence when adding a preset that shares an ID with an existing item.
   - Added feature test `test_duplicate_item_ids_are_disambiguated_by_service` in `UserQuickAccessApiTest.php` verifying backend ID disambiguation.

## Verification
- **PHPUnit Feature Suite**: `php artisan test tests/Feature/User/UserQuickAccessApiTest.php` passed (10/10 passed, 80 assertions).
- **Vitest Quick Access Suite**: `npx vitest run quickAccessService.test.ts QuickAccessSettingsPage.test.tsx QuickAccessMenu.test.tsx QuickAccessMenuSync.test.tsx` passed (40/40 passed across 4 files).
- **Vitest Navigation & Layout Suite**: `navigationUtils.test.ts`, `Sidebar.test.tsx`, `WorkspaceLayout.test.tsx`, `AppHeader.test.tsx`, `rentalNavigation.test.ts` passed (29/29 passed across 5 files).
- **TypeScript Typecheck**: `npm run typecheck` passed with 0 errors.
- **Vite Production Build**: `npm run build` completed successfully with 0 errors in 8.49s.
- **Git Hygiene**: `git diff --check` passed cleanly with 0 whitespace or formatting issues.
