# Document WhatsApp share popup fix

## Summary

- Fixed Service Invoice and Purchase Order WhatsApp sharing for unverified customer and supplier recipients.
- Removed the pre-opened blank browser tab from both share actions.
- The verification warning now remains visible in the active application tab; after confirmation, the validated `wa.me` URL opens in that same tab.
- This avoids browser popup blocking and background-tab confirmation behavior that previously made sharing appear unresponsive.

## Diagnosis

- The latest local Service Invoice and Purchase Order were both eligible to share and had active WhatsApp numbers, but both recipient states were unverified.
- Read-only runtime simulation confirmed the backend generated valid `wa.me` links containing signed PDF URLs.
- Both deployed API routes were present and protected as expected.
- The shared frontend flow opened a blank tab before awaiting the API and then invoked `window.confirm` from the original tab, which can be hidden or suppressed after focus moves to the blank tab.

## Verification

- Focused Vitest coverage passed: 2 files, 4 tests.
- ESLint passed for both changed detail pages and the new regression test.
- Production Vite build passed with 668 modules transformed.
- Backend WhatsApp policy, link, message, and shared-PDF tests passed before the frontend change: 7 tests, 25 assertions.
- `git diff --check` passed.
