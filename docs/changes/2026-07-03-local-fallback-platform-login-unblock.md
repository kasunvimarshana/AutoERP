# Local fallback platform-host tenant login unblock

Date: 2026-07-03

## Problem

When `TENANT_LOCAL_FALLBACK_ENABLED=true`, the backend resolver can resolve the configured local/testing tenant from a central host without storing `localhost`, loopback addresses, or the platform host as tenant-domain records. The frontend login helper still treated a built app on the configured platform host as platform-only unless the host was loopback, so `https://autoerp.tapromall.com/login` showed the workspace-hostname prompt instead of the tenant login form.

## Correction

Aligned the frontend platform-host helper with the backend local/testing fallback policy. When the frontend is built with `VITE_TENANT_LOCAL_FALLBACK_ENABLED=true`, the login page no longer blocks tenant login on the configured platform host.

Public tenant-domain validation remains strict. Localhost, loopback addresses, and the platform host are still not stored as production tenant domains.

## Verification

- `npx vitest run resources/js/modules/auth/platformHost.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
