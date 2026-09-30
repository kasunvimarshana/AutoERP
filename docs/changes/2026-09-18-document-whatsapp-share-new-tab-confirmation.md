# Document WhatsApp share new-tab confirmation

## Summary

- Corrected the Service Invoice and Purchase Order WhatsApp share interaction to preserve the originally approved new-tab handoff.
- The share click immediately opens a pending new tab to retain the browser's user-gesture context.
- For unverified customer or supplier numbers, the confirmation is now invoked by that pending tab rather than by the original AutoERP tab.
- Confirming navigates the same pending tab to the validated `wa.me` URL; cancelling or encountering an error closes it.
- If the browser blocks the pending tab, confirmation and WhatsApp navigation safely fall back to the original tab.

## History clarification

- The original September 11 implementation pre-opened a new tab and later navigated it to WhatsApp.
- The September 12 verification change added `window.confirm` from the original page while retaining the pending WhatsApp tab.
- The earlier same-tab correction recorded on September 18 is superseded by this user-confirmed requirement: both the warning and WhatsApp handoff belong to the new tab when the browser permits it.

## Verification

- Focused Vitest coverage passed: 2 files, 5 tests.
- The shared navigation test directly verifies that confirmation is executed on the prepared WhatsApp window.
- Source-level regressions verify both Service Invoice and Purchase Order handlers use the pending window for confirmation, cancellation, and navigation.
- ESLint passed for every changed frontend file.
- Production Vite build passed with 668 modules transformed.
