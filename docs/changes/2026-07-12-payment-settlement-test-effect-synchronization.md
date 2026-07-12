# Payment settlement test effect synchronization

Date: 2026-07-12

## Evidence

The Vehicle Rental invoice settlement behavioral test rendered the fetched invoice successfully, but asserted the prefilled payment total immediately after finding the party name in the invoice summary. The settlement form is initialized by a React effect after the invoice render, so the summary party could be visible while the locked settlement party and payment balance still held their initial values.

The production workflow behaved as designed; the test synchronized with the wrong observable state.

## Correction

- The mocked payment-line table now exposes its calculated total through a stable test id.
- The test waits until the settlement effect has populated both the authoritative party and invoice balance.
- After selecting a payment method, the test waits until the direction-specific submit action is enabled before submitting.
- The existing assertions for payment type, direction, party, currency, instrument direction, and atomic specific-invoice allocation remain unchanged.

## Scope

- No production Payment, Invoice, or Vehicle Rental behavior changed.
- No timing delay, compatibility shim, or arbitrary timeout was introduced.
- The correction remains within the Payment-owned behavioral test.

## Verification required

Run from the latest `worktree-0.0.8` branch:

```bash
npx vitest run resources/js/modules/payment/pages/PaymentEntryInvoiceSettlement.test.tsx --reporter=dot --silent
npm run test
```
