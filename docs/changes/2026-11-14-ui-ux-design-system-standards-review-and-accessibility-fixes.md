# Change Record: UI/UX Design System Standards Review and Accessibility Fixes

**Date:** 2026-11-14  
**Author:** Antigravity  
**Module:** Shared UI/UX Design System (`resources/js/shared`, `resources/js/app/layout`, `resources/js/modules/not-found`)

---

## 1. Overview and Intent

Following a skeptical review of the prior UI/UX overhaul attempt, several regressions, accessibility barriers, and incorrect route assumptions were uncovered and resolved:
1. **StatusBadge Token Regression**: The prior attempt had changed positive badge tokens to `bg-emerald-50 text-emerald-700`, which broke invoice status badge settlement tests (`InvoiceStatusBadge.test.tsx` expecting standard `bg-emerald-100 text-emerald-800`).
2. **Platform Route Assumption**: The prior attempt hardcoded breadcrumbs to `/platform` (a non-existent route) instead of the authoritative `PLATFORM_HOME_PATH` (`/administration/saas-tenants`), causing broken navigation in Platform Administration.
3. **NotFoundPage Context**: The 404 page unconditionally redirected users to `/dashboard`, breaking the flow for Platform Operators who need to return to Platform Administration.
4. **DataTable Keyboard Navigation**: Rows with `onRowClick` lacked `tabIndex={0}`, ARIA roles, and keyboard event support (Enter/Space), preventing keyboard-only users from interacting with clickable rows.
5. **Dropdown Accessibility and WebKit Quirks**: `ActionMenu` and `AppHeader` user menus lacked Safari `[&::-webkit-details-marker]:hidden` support (resulting in doubled triangles on WebKit/iOS), failed to restore keyboard focus to the `<summary>` trigger when dismissed via Escape, and lacked auto-dismissal on action click inside `AppHeader`.
6. **Form Control Robustness**: `Select` was upgraded to hide the single-select chevron when `multiple` is active, dim the chevron when `disabled`, and `Button` was upgraded to respect `aria-disabled` on `LinkButton`.

---

## 2. Changes Made

- **File:** `resources/js/shared/components/StatusBadge.tsx`
  - Restored standard high-contrast badge tokens (`bg-emerald-100 text-emerald-800 border border-emerald-200/80`, `bg-rose-100 text-rose-800`, `bg-blue-100 text-blue-800`, `bg-amber-100 text-amber-800`, `bg-slate-100 text-slate-700`).
  - Preserved expanded domain status categorization while ensuring 100% test suite compatibility.
- **File:** `resources/js/app/layout/WorkspaceLayout.tsx` & `AppHeader.tsx`
  - Passed `homePath` and `homeLabel` from `WorkspaceLayout` into `AppHeader`.
  - Replaced hardcoded `/platform` with authoritative `PLATFORM_HOME_PATH` (`/administration/saas-tenants`).
  - Added `[&::-webkit-details-marker]:hidden` to suppress WebKit summary markers.
  - Restored focus to `<summary>` trigger on Escape keydown.
  - Added auto-dismissal on button/link click inside the user account dropdown.
- **File:** `resources/js/shared/components/ActionMenu.tsx`
  - Added `[&::-webkit-details-marker]:hidden` to summary element.
  - Restored focus to `<summary>` trigger on Escape keydown.
- **File:** `resources/js/modules/not-found/NotFoundPage.tsx`
  - Integrated `useAuth()` to dynamically link to `PLATFORM_HOME_PATH` for platform operators and `DASHBOARD_PATH` for tenant users.
- **File:** `resources/js/shared/components/DataTable.tsx`
  - Computed `isClickable` across both `rowHref` and `onRowClick`.
  - Added `tabIndex={isClickable ? 0 : undefined}`, `role={rowHref ? 'link' : isClickable ? 'button' : undefined}`, keyboard Enter/Space activation, and focus rings.
- **File:** `resources/js/shared/components/Select.tsx`
  - Guarded single-select chevron and blank placeholder against multi-select mode (`multiple`).
  - Added disabled state styling to chevron (`text-slate-300` when disabled).
- **File:** `resources/js/shared/components/Button.tsx`
  - Added `aria-disabled:cursor-not-allowed aria-disabled:opacity-60 aria-disabled:pointer-events-none` to `buttonClassName` for accessible links and buttons.
- **Test Suites Added:**
  - `resources/js/shared/components/Select.test.tsx`: Verified rendering, accessible descriptions, validation error attributes, and multiple mode.
  - `resources/js/shared/components/DataTable.test.tsx`: Verified empty state and keyboard activation with Enter/Space for `onRowClick`.
  - `resources/js/app/layout/AppHeader.test.tsx`: Verified platform home path wiring and Escape key focus restoration.
  - `resources/js/modules/not-found/NotFoundPage.test.tsx`: Verified platform operator vs tenant user home destination.

---

## 3. Verification

- Ran `npm run typecheck` (`tsc --noEmit`): **0 errors**.
- Ran full test suite `npx vitest run`: **149 test files passed, 558 tests passed (0 failed)**.
- Ran `npm run build` (`vite build`): **Production build succeeded in 17.14s**.
