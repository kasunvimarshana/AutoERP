# Align vehicle service payment layout with Selling payment

## Request

Give the vehicle service job payment page the same visual structure as the Selling payment page without changing its payment and credit workflows.

## Changes

- Added the selected invoice, invoice total, and outstanding balance cards at the top of the vehicle service payment form. The invoice selector remains controlled, and the completed invoice stays visible after payment options refresh.
- Arranged payment date and Direct/Credit choice beneath the invoice cards, and matched the cash, card, and bank transfer sections to the Selling page's color and spacing.
- Restyled the right payment summary with a clear payment total and balance or credit amount card. Added a Cancel/Return to service job link.
- Kept the existing payment submission, credit assessment and confirmation, amount calculations, Print bill, and WhatsApp share handlers.
- No database relationships or backend APIs changed.

## Verification

- TypeScript typecheck and `git diff --check` passed. Targeted ESLint completed with one pre-existing React effect warning and no errors.
- Automated tests were not run.
