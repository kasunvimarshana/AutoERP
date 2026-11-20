# Change Record: Quick Access End-to-End Audit & Hardening

**Date:** 2026-11-20  
**Scope:** `resources/js/modules/settings`, `resources/js/app/layout`  
**Purpose:** End-to-end audit and resolution of preset inconsistencies, dynamic module/action registration, inaccessible module leakage, cross-tab storage re-rendering, summary counter metrics, and empty/default reset behavior.

## Summary of Changes

1. **Workspace Navigation Section Unification**:
   - `quickAccessService.ts` & `QuickAccessSettingsPage.tsx`: Switched source navigation sections from `tenantNavigationSections` to `tenantWorkspaceNavigationSections`. This ensures that dynamic modules attached in `tenantWorkspaceNavigation.ts` (Vehicle Rental, Human Resources, Units of Measure, and enhanced Inventory/Voucher routes) are fully recognized as valid presets and permitted actions in Quick Access.

2. **Elimination of Preset Module Leakage & Duplication**:
   - `quickAccessService.ts`: Removed redundant and flawed `queryPresets` array. The previous implementation checked `seenPaths.has(stripQuery(extra.to))`, causing Vehicle Service query presets (e.g. `/invoices?view=service`) to leak into the preset selector for tenants with Vehicle Service disabled simply because the generic Invoices module was enabled. Presets are now extracted purely from the permission-filtered workspace sections, preventing any inaccessible module leakage.

3. **Single Source of Truth for Route Accessibility**:
   - `quickAccessService.ts`: Extracted and exported `isQuickAccessItemAccessible(item, sections)`. Replaces duplicate manual iteration and matching logic previously in `QuickAccessMenu.tsx` and `QuickAccessSettingsPage.tsx`. Accurately checks ID matches, exact normalized path matches, candidate path matches, base path matches, and route hierarchy matches.
   - `QuickAccessMenu.tsx` & `QuickAccessSettingsPage.tsx`: Replaced duplicated link and candidate map logic with `isQuickAccessItemAccessible`.

4. **Summary Metrics Alignment & Restricted Item Badge**:
   - `QuickAccessSettingsPage.tsx`: Fixed summary counter where `Visible` previously counted all `enabled: true` items without checking if their module was disabled or inaccessible. `Visible` now counts only items that are both enabled AND accessible in the active workspace. Added an amber `Restricted: N` badge when enabled items belong to inaccessible modules, giving users clear visibility into why certain shortcuts do not appear in the menu.

5. **Preset Selection ID Preservation**:
   - `QuickAccessSettingsPage.tsx`: In `handlePresetSelect`, populated `id: found.id` alongside label, path, group, and icon. This ensures that when adding a registered preset, the shortcut retains its registered navigation item ID, allowing active route matching (`activeItemId === item.id`) in the header menu to identify active pages cleanly.

6. **Storage Event Filter Guard**:
   - `quickAccessService.ts` & `QuickAccessMenu.tsx`: Filtered cross-tab `storage` event handlers by storage key (`if (event.key && event.key !== storageKey && event.key !== versionKey) return;`), preventing unrelated storage modifications from causing spurious re-reads and re-renders.

7. **Dead Code Elimination & Empty State Feedback**:
   - `quickAccessService.ts`: Removed unused `isInitialMount` ref.
   - `QuickAccessSettingsPage.tsx`: Connected empty state "Restore Defaults" button to `handleReset`, ensuring feedback is properly presented to the user.

8. **Reset-to-Default vs Empty Menu Evaluation**:
   - Verified that "Reset to Defaults" restoring predefined items (`DEFAULT_ITEMS`) while preserving empty states when all items are explicitly deleted is the sound, intuitive, and correct behavior across both frontend and backend.

## Verification
- **PHPUnit Feature Tests**: `php artisan test tests/Feature/User/UserQuickAccessApiTest.php` passed (9/9).
- **Vitest Frontend Tests**: `quickAccessService.test.ts`, `QuickAccessSettingsPage.test.tsx`, `QuickAccessMenu.test.tsx`, `QuickAccessMenuSync.test.tsx` passed (39/39).
- **Related Navigation & Layout Tests**: `navigationUtils.test.ts`, `Sidebar.test.tsx`, `WorkspaceLayout.test.tsx`, `AppHeader.test.tsx`, `rentalNavigation.test.ts` passed (29/29).
- **TypeScript Typecheck**: `npm run typecheck` passed with 0 errors.
- **Production Build**: `npm run build` compiled cleanly in 8.63s.
- **Git Hygiene**: `git diff --check` passed cleanly without whitespace or lint errors.
