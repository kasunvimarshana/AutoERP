# Customer dropdown WhatsApp number

## Request

Keep the customer profile's Phone and WhatsApp Number fields as they are. Remove the customer code from customer dropdown options and show the customer's WhatsApp number beside the name. Keep existing phone and WhatsApp number search behavior unchanged.

## Changes

- Update the shared customer lookup presentation to show the customer name and WhatsApp number, with no customer code in the option or selected label.
- Apply the same presentation to customer lookups used by selling, vehicle filters, reports, vehicle service, and rental.
- Continue using the existing customer lookup API. Customer list and lookup searches already match both `phone` and `mobile`; no search or backend behavior change was needed.
- Leave customer profile fields unchanged.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
