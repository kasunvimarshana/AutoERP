# Vehicle service job tabs keep-mounted cache

Date: 2026-07-12

## Problem

The vehicle service job detail tabs unmounted their content every time the active tab changed. That caused each tab's frontend state and `useApi` data to be discarded, so returning to an already opened tab triggered another server request and showed a loader again.

## Change

- added an optional `keepMounted` mode to the shared `TabPanel` component;
- updated the vehicle service job detail page to keep all opened tabs mounted after their first visit;
- preserved the existing on-demand behavior so unopened tabs still do not mount until the user first opens them;
- added a shared tab-component test that verifies hidden keep-mounted panels stay in the DOM instead of being unmounted.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/shared/components/Tabs.test.tsx --reporter=dot`

## Scope

This change is limited to frontend tab persistence behavior and the vehicle service job detail page. It reduces repeated API calls and loader flashes when switching back to tabs that the user has already opened once.
