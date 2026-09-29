# Customer dropdown name and number correction

## Request

Remove customer code from dropdown options and place the WhatsApp number immediately beside the customer name.

## Changes

- Show only customer name followed by WhatsApp number in the shared customer and rental dropdown options.
- Keep the selected customer label as the name only.
- Keep customer form fields and phone/WhatsApp search behavior unchanged.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
