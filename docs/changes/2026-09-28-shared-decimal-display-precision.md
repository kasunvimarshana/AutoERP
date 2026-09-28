# Shared decimal display precision

## Request

The user marked quantity, unit price, unit cost, discount, tax, and charge inputs that still displayed six decimal places and requested two or three decimal places throughout the system.

## Changes

- Make shared decimal inputs display two decimal places minimum and three maximum while retaining the full backend value unless edited.
- Keep the focused input buffer separate from the stored decimal so intermediate typing such as `100.` remains possible.
- Cap shared quantity display formatting at three decimal places.
- Apply formatted quantity displays to the Vehicle Service invoice line tables.

## Verification

- Pending TypeScript and production build checks.
- TypeScript check passed (`npm run typecheck`).
- Production frontend build passed (`npm run build`).
- `git diff --check` passed; Git reported only CRLF normalization notices on two previously edited TSX files.
