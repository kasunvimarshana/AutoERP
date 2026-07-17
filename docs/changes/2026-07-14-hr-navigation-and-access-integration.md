# HR navigation and access integration

Date: 2026-07-14

## Problem

The Human Resources module could be enabled in a tenant plan and its backend routes and pages existed, but the tenant workspace did not register an HR navigation item. The frontend also used one broad `/hr/*` route entitlement without the backend's granular HR permissions, so direct route access and visible page actions were not aligned with the owning HR authorization contract.

## Correction

- Added one frontend HR permission catalogue matching the backend HR authorization service.
- Added feature-owned entitlements for employee list, create, detail, and edit routes with the exact owning permissions.
- Removed the obsolete broad HR rule from administration route ownership.
- Added a Human Resources workspace item under Operations with permission-filtered Employees and Create Employee links.
- Composed feature-owned HR navigation into the tenant workspace without editing or duplicating the existing central navigation tree.
- Hid create, edit, activate, and deactivate controls when the current user does not hold the corresponding HR permission. Super-admin behavior remains governed by the shared access-control policy.
- Added focused tests for tenant-plan enablement, permission-filtered navigation, and exact HR route entitlements.

## Security and behavior

Enabling the HR module does not grant HR permissions. The module entitlement, selected organization unit, and user permissions remain separate required access boundaries. Backend authorization remains authoritative; the frontend now presents the same policy instead of exposing actions that would fail with a 403 response.

A tenant session loaded before a plan change must refresh its current-user profile, normally through a page reload or new login, before the updated `enabled_modules` value is available to the frontend.

## Relationship review

No database, model, API, or domain relationship was changed. The defect was limited to frontend navigation registration and access-policy ownership. Existing employee, department, designation, qualification, and organization relationships remain valid and were intentionally left unchanged.

## Verification

Run from the authoritative `worktree-0.0.8` branch:

- `git diff --check`
- `npx vitest run resources/js/modules/hr/hrAccessIntegration.test.ts --reporter=dot --silent`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run test`
- `npm run build`
- `php artisan test --filter=HrPermissionBoundaryTest`
- `php artisan test`
- `composer test:mysql`
