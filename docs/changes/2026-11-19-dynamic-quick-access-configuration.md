# Dynamic and Configurable Quick Access Menu

**Date:** 2026-11-19  
**Modules:** Settings, Layout & Header Navigation, Workspace Routing

Made the Quick Access header menu fully dynamic and configurable, persisted per-user via browser LocalStorage with reactive synchronisation, and added a dedicated Quick Access submenu under Settings in the sidebar navigation.

### Key Changes
1. **Dynamic Quick Access Service & Storage (`quickAccessService.ts` & `quickAccessService.test.ts`)**:
   - Created configuration data model (`QuickAccessItemConfig`) for shortcuts specifying `id`, `label`, `to`, `group`, `icon`, `enabled`, and `order`.
   - Implemented safe LocalStorage persistence under key `autoerp.quick_access_items` with fallback to standard default items (`Create Service Job`, `Service Job List`, `Create Selling`, and `Selling List`).
   - Fixed empty state preservation: storing an explicit empty array `[]` preserves user-emptied state across reloads without resurrecting defaults.
   - Enhanced `normalizePath` to trim whitespace, enforce leading slash, strip trailing slashes, and preserve query parameters.
   - Corrected preset routes in `SYSTEM_PRESET_ITEMS` to strictly match authoritative AutoERP routes (e.g. `/vehicle-service/promotions`, `/vehicle-rental/billing`, `/selling/returns`, `/uom-convert`).
   - Added `autoerp:quick-access-updated` custom event and storage event synchronization so changes in configuration update the header menu instantaneously across all active tabs without full page reloads.
   - Built custom hook `useQuickAccessConfig` synchronizing ordered state immediately.

2. **Dynamic Header Quick Access Menu (`QuickAccessMenu.tsx` & `QuickAccessMenu.test.tsx`)**:
   - Replaced hardcoded `QUICK_ACCESS_GROUPS` with dynamic items retrieved from LocalStorage and reactive event updates.
   - Preserved workspace permission safeguards: links only render if permitted by the user's current filtered navigation sections, checking item ID, direct path/candidate matches, or navigation hierarchy.
   - Enhanced active item detection to support URLs with query parameters.
   - Dynamically grouped visible entries by their configured category.
   - Added "Customize" shortcut link directly in the dropdown header to quickly navigate to the Quick Access settings page.
   - Added unit test coverage for query string active state, route matching, and empty storage handling.

3. **Sidebar Settings Submenu & Route Entitlement (`navigationConfig.ts`, `navigationUtils.test.ts`, `administrationRouteEntitlements.ts`, `router.tsx`)**:
   - Converted the `settings` navigation item in `tenantNavigationSections` from a single link into an expandable module with submenus:
     - `Configuration` (`/settings` - system configuration overrides)
     - `Quick Access` (`/settings/quick-access` - shortcut customization)
   - Registered `/settings/quick-access` in `administrationRouteEntitlements` with tenant entitlement and added route in `router.tsx`.
   - Updated navigation matching tests verifying parent-child hierarchy and breadcrumbs for both Settings entries.

4. **Dedicated Quick Access Settings Page (`QuickAccessSettingsPage.tsx` & `QuickAccessSettingsPage.test.tsx`)**:
   - Built comprehensive management page with:
     - Metric counter badges for total, visible, and hidden shortcuts.
     - Interactive table with single-click visibility switches, reordering (Move Up / Down), editing, and deletion.
     - Add / Edit modal dialog with controlled system presets dropdown auto-fill, duplicate route rejection, single-slash path validation, category grouping, and icon selection.
     - Live Dropdown Preview panel showing how the menu appears in real-time.
     - Confirmation dialogs for deletion and resetting to defaults.
   - Added unit tests covering render, visibility toggles, reordering, duplicate detection, invalid route rejection, preset selection, deletion, and reset.

### Verification
- Vitest unit tests: 57/57 passed across 9 test files (`QuickAccessMenu.test.tsx`, `QuickAccessSettingsPage.test.tsx`, `quickAccessService.test.ts`, `navigationUtils.test.ts`, `Sidebar.test.tsx`, `WorkspaceLayout.test.tsx`, `AppHeader.test.tsx`, `ConfigurationSettingsPanel.test.tsx`, `rentalNavigation.test.ts`).
- Full Vitest suite: 632/632 passed across 164 files.
- TypeScript typecheck (`tsc --noEmit`): 0 errors.
- Production bundle build (`npm run build`): compiled cleanly in 9.98s.
- `git diff --check`: no whitespace or formatting errors.
