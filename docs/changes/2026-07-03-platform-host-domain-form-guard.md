# Platform host tenant-domain form guard

Date: 2026-07-03

## Problem

The Tenant module already rejects the platform host as a tenant domain, but the platform tenant-domain form still allowed operators to submit the current platform hostname. That made the request reach backend validation or recent-auth checks before the user saw the actual routing mistake.

## Correction

Added a focused frontend presentation rule that compares the entered tenant hostname with the current platform hostname and rejects the same host before submitting the domain action.

Wired the rule into the platform tenant-domain form so `autoerp.tapromall.com` cannot be submitted as a tenant domain while the operator is already using `autoerp.tapromall.com` as the platform host. Operators must use a separate public tenant workspace hostname.

## Verification

- `npx vitest run resources/js/modules/tenant/tenantPresentation.test.ts --reporter=dot --silent=true`
- `npx vitest run resources/js/modules/tenant/components/PlatformTenantDomainsPanel.test.tsx --reporter=dot --silent=true`
- `npm run typecheck`
- `git diff --check`
