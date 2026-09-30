# Keep customer code beside WhatsApp number in dropdowns

## Request

Keep the customer code in customer dropdowns while showing the WhatsApp number beside the customer name.

## Changes

- Show `Code - Name` on the left and WhatsApp number on the right in shared and rental customer dropdowns.
- Keep the selected customer label as `Code - Name`.
- Leave customer fields and search behavior unchanged.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
