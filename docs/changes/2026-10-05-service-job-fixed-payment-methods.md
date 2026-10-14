# Replace add-method payment entry with fixed method amounts

## Request

Use fixed Cash, Card, and Bank transfer amount fields for service job payments, with card brand selection and reference details for card and bank transfer.

## Changes

- Replace the dynamic Add method rows with fixed Cash, Card, and Bank transfer sections.
- Show a payment method selector only when multiple active inbound methods exist for that category.
- Keep cash change calculation, invoice balance checks, card brand choices, and payment review/finalization behavior.
- Add Card and Bank transfer reference inputs and send them through the existing payment line reference and instrument fields.
- Reuse the existing payment line reference storage; no database migration was needed.

## Verification

- TypeScript typecheck passed.
- ESLint passed with the existing invoice auto-selection hook warning in the payment page.
- `git diff --check` passed.
- Automated tests were not run.
