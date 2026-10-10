# Improve service job payment settlement entry

## Request

Use the old settlement screen as a reference for direct cash/card payments and customer credit eligibility.

## Changes

- Add Direct Payment and Credit Payment choices; Credit Payment is offered only when the bill-to customer's active credit profile allows credit.
- For cash, calculate the invoice-applied amount and change from a UI-only Cash received value. Send only the applied amount to the payment API.
- Add Visa, Master, and Amex card radio choices, keep the selected brand with the saved payment line, and show it on payment details.
- Keep the existing payment transaction and invoice balance validation flow for direct receipts.

## Verification

- Vehicle Service payment page tests passed (6 tests), including partial cash, cash change, card brand selection, and credit eligibility display.
- Vehicle Service backend tests passed for card brand persistence and active customer credit eligibility (2 tests, 3 assertions).
- TypeScript typecheck passed.
- PHP syntax checks passed for changed request, service, resource, enum, and engine test files.
- ESLint passed with two existing hook warnings in the payment page's invoice and method auto-selection effect.
- `git diff --check` passed.
