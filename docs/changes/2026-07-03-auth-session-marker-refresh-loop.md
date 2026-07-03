# Auth session marker refresh loop

Date: 2026-07-03

## Problem

Restoring an already authenticated user rewrote the cross-tab authentication session marker. Other open browser tabs treated that marker update as a new session change, reloaded `/me`, and rewrote the marker again. This created a refresh loop where guarded pages repeatedly showed "Checking access..." and page API requests were cancelled and restarted.

## Correction

Added an explicit option for auth session commits to update in-memory/session context without notifying other tabs. User restoration now commits silently, while login and explicit organization-unit switches can still broadcast a single session-change marker to other tabs.

Added regression coverage to ensure silent commits preserve the existing marker and `AuthProvider` bootstrap restoration does not rotate the marker.

## Verification

- `npx vitest run resources/js/shared/api/authSessionStorage.test.ts resources/js/modules/auth/AuthProviderSessionMarker.test.tsx resources/js/modules/auth/AuthProvider.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run build`
