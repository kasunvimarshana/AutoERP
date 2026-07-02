# Delayed auth guard loading

Date: 2026-07-02

## Problem

Auth and entitlement route guards rendered full-page loading states immediately while session, permission, or module access checks were in progress. Fast checks could briefly paint `Checking access...` during normal navigation or session restore, creating a visible flash even when access was valid.

## Correction

Added a small delayed guard loading component for auth route guards. Protected, tenant, platform, permission, and tenant-entitlement guards now wait briefly before showing their full-page checking message, while still showing the existing loading feedback when checks take longer.

Normal page-level loading states were left unchanged.

## Verification

- `npx vitest run resources/js/modules/auth/GuardLoadingState.test.tsx resources/js/modules/auth/ProtectedRoute.test.tsx --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run lint`
- `git diff --check`
