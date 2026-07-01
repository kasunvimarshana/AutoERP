# Local fallback tenant login gate

Date: 2026-07-01

## Problem

Local built frontend assets showed the tenant workspace hostname gate on `127.0.0.1:8000`, even though backend tenant routing had explicit local fallback enabled for the `AUTOERP` tenant.

## Root cause

The login page skipped the platform-host tenant workspace gate only in Vite dev mode. A local production build could still treat the configured platform URL as a central platform host and require an HTTPS workspace hostname, which does not match the backend local fallback contract.

## Correction

The frontend now reads `VITE_TENANT_LOCAL_FALLBACK_ENABLED` and bypasses the workspace hostname gate on loopback hosts when local fallback is enabled. `.env` and `.env.example` expose that non-secret flag from `TENANT_LOCAL_FALLBACK_ENABLED`.

## Verification

- `npx vitest run resources/js/modules/auth/platformHost.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
