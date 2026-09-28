# Vehicle Service decimal discount input and money display

## Cause

The shared decimal input rejected a trailing decimal separator while the user was typing. For example, entering `100.50` requires the controlled input to temporarily hold `100.`, but the strict decimal check rejected that intermediate state and prevented the next digit from being entered.

Vehicle Service money summaries also rendered raw API decimal strings in several places, exposing six decimal places.

## Changes

- Accept an intermediate trailing decimal point in decimal inputs and remove it on blur if no fractional digits are entered.
- Keep calculation and API precision unchanged.
- Format displayed money with at least two and at most three fractional digits, and use the shared money display in Vehicle Service line, invoice, item, and pricing summary views.

## Verification

- Pending TypeScript and production build checks.
- TypeScript check passed (`npm run typecheck`).
- Production frontend build passed (`npm run build`).
- `git diff --check` passed; Git reported only existing-style CRLF normalization notices for two edited TSX files.
