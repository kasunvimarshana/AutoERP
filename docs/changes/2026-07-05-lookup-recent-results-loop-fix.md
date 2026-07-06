# Lookup recent results loop fix

Date: 2026-07-05

## Problem

The recent-results reopening improvement for lookup dropdowns introduced a render/request loop risk in the shared select component. When a lookup response updated recent-results state, the same open-search effect could react to that store change and re-trigger the same request repeatedly, causing the browser to become unresponsive during item search.

## Correction

Adjusted the shared lookup select so recent-results state is read through a ref inside the minimum-search fallback path rather than participating in the main search effect dependency cycle.

This preserves the UX improvement for reopening recent results while preventing repeated identical search execution after each response.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/components/GenericLookupSelect.tsx`
