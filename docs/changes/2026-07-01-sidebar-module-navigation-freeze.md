# Sidebar module navigation freeze

Date: 2026-07-01

## Problem

The left sidebar could appear frozen when the current route belonged to one module and the user clicked another sidebar module. The clicked module state changed internally, but the active route's module always took priority during rendering.

## Root cause

`WorkspaceLayout` resolved `expandedModuleId` by preferring the active route module before the user's selected module. This prevented users from browsing another module's child links while staying on the current route.

## Correction

Sidebar module expansion now allows the user's clicked module to override the active route for the current location only. After navigation changes, expansion falls back to the active route module so the sidebar remains aligned with the page context.

## Verification

- `npx vitest run resources/js/app/layout/WorkspaceLayout.test.tsx resources/js/app/layout/Sidebar.test.tsx --reporter=dot --silent=true`
- `npm run typecheck`
